<?php

namespace App\Application\Sepa;

use App\Application\Audit\AuditLogger;
use App\Application\Club\CurrentClub;
use App\Application\Sepa\Xml\Pain008XmlBuilder;
use App\Application\Sepa\Xml\ValidatePain008Xml;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use App\Domain\Sepa\Models\SepaDebitItem;
use App\Domain\Sepa\Models\SepaDebitRun;
use DomainException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final readonly class ExportSepaDebitRun
{
    public function __construct(
        private CurrentClub $currentClub,
        private ValidateSepaDebitRunForExport $validator,
        private Pain008XmlBuilder $builder,
        private ValidatePain008Xml $xmlValidator,
        private SepaIdentifierGenerator $identifierGenerator,
        private AuditLogger $audit,
    ) {}

    public function handle(
        SepaDebitRun $run,
    ): SepaDebitRun {
        $clubId = $this->currentClub->id();

        if ((string) $run->club_id !== $clubId) {
            throw new DomainException(
                'Der Lastschriftlauf gehört nicht zum aktuellen Verein.'
            );
        }

        return DB::transaction(function () use (
            $run,
            $clubId,
        ): SepaDebitRun {
            $lockedRun = SepaDebitRun::query()
                ->whereKey($run->getKey())
                ->where('club_id', $clubId)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedRun->status === SepaDebitRunStatus::Exported
                && $lockedRun->xml_storage_path !== null
            ) {
                return $lockedRun;
            }

            if (
                $lockedRun->status !== SepaDebitRunStatus::Prepared
            ) {
                throw new DomainException(
                    'Dieser Lastschriftlauf kann nicht exportiert werden.'
                );
            }

            $configuration = $this->currentClub
                ->get()
                ->sepaConfiguration;

            if ($configuration === null) {
                throw new DomainException(
                    'Die SEPA-Konfiguration fehlt.'
                );
            }

            $this->validator->validate(
                $lockedRun,
                $configuration,
            );

            if ($lockedRun->message_id === null) {
                $lockedRun->message_id = $this->identifierGenerator
                    ->messageId($lockedRun);
            }

            if ($lockedRun->payment_information_id === null) {
                $lockedRun->payment_information_id = $this->identifierGenerator
                    ->paymentInformationId($lockedRun);
            }

            $lockedRun->save();

            $lockedRun->items()
                ->whereNull('end_to_end_id')
                ->eachById(function (SepaDebitItem $item): void {
                    $item->update([
                        'end_to_end_id' => $this->identifierGenerator->endToEndId($item),
                    ]);
                });

            $xml = $this->builder->build(
                $lockedRun,
                $configuration,
            );

            $this->xmlValidator->validate($xml);

            $checksum = hash('sha256', $xml);

            $path = sprintf(
                'sepa/exports/%s/%s.xml.enc',
                $clubId,
                $lockedRun->getKey(),
            );

            $disk = Storage::disk('local');

            try {
                $stored = $disk->put(
                    $path,
                    Crypt::encryptString($xml),
                );

                if (! $stored) {
                    throw new RuntimeException(
                        'Der verschlüsselte Export konnte nicht gespeichert werden.'
                    );
                }

                $lockedRun->update([
                    'status' => SepaDebitRunStatus::Exported,
                    'xml_format' => Pain008XmlBuilder::FORMAT,
                    'xml_storage_path' => $path,
                    'xml_sha256' => $checksum,
                    'xml_generated_at' => now(),
                    'exported_at' => now(),
                ]);

                $this->audit->log(AuditAction::SepaDebitRunExported, $lockedRun, properties: [
                    'club_id' => $lockedRun->club_id,
                    'run_id' => $lockedRun->getKey(),
                    'xml_format' => $lockedRun->xml_format,
                    'xml_storage_path' => $lockedRun->xml_storage_path,
                    'xml_generated_at' => $lockedRun->xml_generated_at,
                    'exported_at' => $lockedRun->exported_at,
                    'items_count' => $lockedRun->items_count,
                    'total_amount' => $lockedRun->total_amount,
                    'xml_sha256' => $lockedRun->xml_sha256,
                ]);
            } catch (\Throwable $exception) {
                $disk->delete($path);

                throw $exception;
            }

            return $lockedRun->refresh();
        });
    }
}
