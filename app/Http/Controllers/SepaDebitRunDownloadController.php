<?php

namespace App\Http\Controllers;

use App\Application\Audit\AuditLogger;
use App\Application\Club\CurrentClub;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use App\Domain\Sepa\Models\SepaDebitRun;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class SepaDebitRunDownloadController
{
    public function __construct(
        private CurrentClub $currentClub,
        private AuditLogger $audit,
    ) {}

    public function __invoke(
        SepaDebitRun $run,
    ): StreamedResponse {
        abort_unless(
            (string) $run->club_id
            === $this->currentClub->id(),
            404,
        );

        Gate::authorize('export', $run);

        abort_unless(
            $run->status === SepaDebitRunStatus::Exported
            && $run->xml_storage_path !== null
            && $run->xml_sha256 !== null,
            404,
        );

        $disk = Storage::disk('local');

        abort_unless(
            $disk->exists($run->xml_storage_path),
            404,
        );

        $encrypted = $disk->get(
            $run->xml_storage_path
        );

        $xml = Crypt::decryptString($encrypted);

        abort_unless(
            hash_equals(
                $run->xml_sha256,
                hash('sha256', $xml),
            ),
            500,
            'Die Exportdatei ist beschädigt.',
        );

        $filename = sprintf(
            'sepa-%s-%s.xml',
            $run->collection_date->format('Y-m-d'),
            $run->getKey(),
        );

        $this->audit->log(AuditAction::SepaDebitRunDownloaded, $run, properties: [
            'club_id' => $run->club_id,
            'run_id' => $run->getKey(),
            'xml_format' => $run->xml_format,
            'items_count' => $run->items_count,
            'total_amount' => $run->total_amount,
            'xml_sha256' => $run->xml_sha256,
        ]);

        return response()->streamDownload(
            static function () use ($xml): void {
                echo $xml;
            },
            $filename,
            [
                'Content-Type' => 'application/xml; charset=UTF-8',
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
