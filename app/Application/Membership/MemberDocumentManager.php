<?php

namespace App\Application\Membership;

use App\Application\Club\CurrentClub;
use App\Application\Membership\Exceptions\MemberNotInCurrentClub;
use App\Domain\Membership\Enums\MemberDocumentType;
use App\Domain\Membership\Models\Member;
use App\Domain\Membership\Models\MemberDocument;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final readonly class MemberDocumentManager
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {
    }

    public function registerStoredFile(
        Member $member,
        string $storagePath,
        string $originalName,
        MemberDocumentType $type,
        User $uploadedBy,
    ): MemberDocument {
        $this->ensureMemberInCurrentClub(
            $member
        );

        $this->ensurePathBelongsToMember(
            member: $member,
            storagePath: $storagePath,
        );

        $disk = Storage::disk('local');

        if (! $disk->exists($storagePath)) {
            throw new RuntimeException(
                'Die hochgeladene Datei wurde nicht gefunden.'
            );
        }

        return MemberDocument::query()
            ->create([
                'club_id' =>
                    $this->currentClub->id(),

                'member_id' =>
                    $member->getKey(),

                'type' =>
                    $type,

                'original_name' =>
                    $originalName,

                'storage_path' =>
                    $storagePath,

                'mime_type' =>
                    $disk->mimeType(
                        $storagePath
                    )
                        ?: 'application/octet-stream',

                'size' =>
                    $disk->size(
                        $storagePath
                    ),

                'checksum' =>
                    $disk->checksum(
                        $storagePath
                    ),

                'uploaded_by' =>
                    $uploadedBy->getKey(),
            ]);
    }

    private function ensureMemberInCurrentClub(
        Member $member,
    ): void {
        if (
            (string) $member->club_id
            !== $this->currentClub->id()
        ) {
            throw MemberNotInCurrentClub::forMember(
                (string) $member
                    ->getKey()
            );
        }
    }

    private function ensurePathBelongsToMember(
        Member $member,
        string $storagePath,
    ): void {
        $expectedPrefix =
            'members/'
            .$this->currentClub->id()
            .'/'
            .$member->getKey()
            .'/documents/';

        if (
            ! str_starts_with(
                $storagePath,
                $expectedPrefix,
            )
        ) {
            throw new RuntimeException(
                'Ungültiger Dokumentpfad.'
            );
        }
    }
}
