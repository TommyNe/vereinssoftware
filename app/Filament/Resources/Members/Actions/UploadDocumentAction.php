<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Membership\MemberDocumentManager;
use App\Domain\Membership\Enums\MemberDocumentType;
use App\Domain\Membership\Models\Member;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class UploadDocumentAction
{
    public static function make(): Action
    {
        return Action::make(
            'uploadDocument'
        )
            ->label(
                'Dokument hochladen'
            )
            ->icon(
                'heroicon-o-arrow-up-tray'
            )

            ->visible(
                static fn (
                    Member $record
                ): bool => Gate::allows(
                    'manageDocuments',
                    $record
                )
            )

            ->schema([
                Select::make('type')
                    ->label(
                        'Dokumenttyp'
                    )
                    ->required()
                    ->options(
                        collect(
                            MemberDocumentType::cases()
                        )
                            ->mapWithKeys(
                                static fn (
                                    MemberDocumentType $type
                                ): array => [
                                    $type->value => $type->label(),
                                ]
                            )
                            ->all()
                    ),

                FileUpload::make('document')
                    ->label('Datei')
                    ->required()
                    ->disk('local')
                    ->visibility('private')
                    ->directory(static fn (Member $record): string => 'members/'.$record->club_id.'/'.$record->getKey().'/documents')
                    ->storeFileNamesIn('original_name')
                    ->preventFilePathTampering()
                    ->acceptedFileTypes([
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                    ])
                    ->maxSize(10240),
            ])

            ->action(
                static function (
                    array $data,
                    Member $record,
                    MemberDocumentManager $manager,
                ): void {
                    Gate::authorize(
                        'manageDocuments',
                        $record
                    );

                    $user = Auth::user();

                    abort_unless(
                        $user instanceof User,
                        403,
                    );

                    $manager->registerStoredFile(
                        member: $record,
                        storagePath: $data['document'],
                        originalName: $data['original_name'],
                        type: MemberDocumentType::from($data['type']),
                        uploadedBy: $user,
                    );

                    $record->load('documents');

                    Notification::make()
                        ->title('Dokument hochgeladen')
                        ->success()
                        ->send();
                }
            );
    }
}
