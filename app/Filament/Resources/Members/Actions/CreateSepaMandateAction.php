<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Sepa\SepaMandateManager;
use App\Domain\Membership\Models\Member;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class CreateSepaMandateAction
{
    public static function make(): Action
    {
        return Action::make(
            'createSepaMandate'
        )
            ->label(
                'SEPA-Mandat hinzufügen'
            )
            ->icon(
                'heroicon-o-credit-card'
            )
            ->visible(
                static fn (
                    Member $record
                ): bool => Gate::allows(
                    'manageSepaMandates',
                    $record
                )
            )
            ->schema([
                TextInput::make(
                    'mandate_reference'
                )
                    ->label(
                        'Mandatsreferenz'
                    )
                    ->required()
                    ->maxLength(100),

                TextInput::make(
                    'account_holder'
                )
                    ->label(
                        'Kontoinhaber'
                    )
                    ->required()
                    ->maxLength(255),

                TextInput::make('iban')
                    ->label('IBAN')
                    ->required()
                    ->maxLength(42),

                TextInput::make('bic')
                    ->label('BIC')
                    ->maxLength(20),

                DatePicker::make(
                    'signed_at'
                )
                    ->label(
                        'Mandat erteilt am'
                    )
                    ->required(),

                DatePicker::make(
                    'valid_from'
                )
                    ->label(
                        'Gültig ab'
                    ),
            ])
            ->action(
                static function (
                    array $data,
                    Member $record,
                    SepaMandateManager $manager,
                ): void {
                    Gate::authorize(
                        'manageSepaMandates',
                        $record
                    );

                    $user =
                        Auth::user();

                    abort_unless(
                        $user instanceof User,
                        403
                    );

                    $manager->create(
                        member: $record,
                        mandateReference: (string) $data[
                        'mandate_reference'
                        ],
                        accountHolder: (string) $data[
                        'account_holder'
                        ],
                        iban: (string) $data['iban'],
                        bic: filled(
                            $data['bic']
                            ?? null
                        )
                            ? (string) $data[
                        'bic'
                        ]
                            : null,
                        signedAt: CarbonImmutable::parse(
                            $data[
                            'signed_at'
                            ]
                        ),
                        validFrom: filled(
                            $data[
                            'valid_from'
                            ] ?? null
                        )
                            ? CarbonImmutable::parse(
                                $data[
                                'valid_from'
                                ]
                            )
                            : null,
                        createdBy: $user,
                    );

                    Notification::make()
                        ->title(
                            'SEPA-Mandat gespeichert'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
