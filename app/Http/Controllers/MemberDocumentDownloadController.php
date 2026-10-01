<?php

namespace App\Http\Controllers;

use App\Application\Audit\AuditLogger;
use App\Application\Club\CurrentClub;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Membership\Models\MemberDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MemberDocumentDownloadController
{
    public function __invoke(
        MemberDocument $document,
        Request $request,
        CurrentClub $currentClub,
        AuditLogger $audit,
    ): StreamedResponse {
        $user = $request->user();
        $club = $user->clubs()->whereKey($document->club_id)->firstOrFail();
        $currentClub->set($club);
        setPermissionsTeamId($club->getKey());
        $user->unsetRelation('roles')->unsetRelation('permissions');

        Gate::authorize(
            'viewDocuments',
            $document->member
        );

        abort_unless(
            Storage::disk('local')
                ->exists(
                    $document->storage_path
                ),
            404,
        );

        $audit->log(AuditAction::DocumentDownloaded, $document, $user, [
            'club_id' => $document->club_id,
            'member_id' => $document->member_id,
        ]);

        return Storage::disk('local')
            ->download(
                $document->storage_path,
                $document->original_name,
                [
                    'Content-Type' => $document->mime_type,
                ]
            );
    }
}
