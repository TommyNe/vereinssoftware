<?php

namespace App\Http\Controllers;

use App\Domain\Membership\Models\MemberDocument;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MemberDocumentDownloadController
{
    public function __invoke(
        MemberDocument $document,
    ): StreamedResponse {
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

        return Storage::disk('local')
            ->download(
                $document->storage_path,
                $document->original_name,
                [
                    'Content-Type' =>
                        $document->mime_type,
                ]
            );
    }
}
