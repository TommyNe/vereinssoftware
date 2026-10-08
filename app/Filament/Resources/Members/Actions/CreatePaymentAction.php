<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Contribution\PaymentManager;
use App\Domain\Contribution\Enums\PaymentMethod;
use App\Domain\Membership\Models\Member;
use App\Models\User;
use Auth;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

final class CreatePaymentAction
{
    public static function make(): Action
    {
        return Action::make(
            'createPayment'
        )
            ->label(
                'Zahlung erfassen'
            )
            ->icon(
                'heroicon-o-banknotes'
            )
            ->schema([
                TextInput::make('amount')
                    ->label('Betrag')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->suffix('€'),

                Select::make('method')
                    ->label('Zahlungsart')
                    ->required()
                    ->options(
                        collect(
                            PaymentMethod::cases()
                        )
                            ->mapWithKeys(
                                static fn (
                                    PaymentMethod $method
                                ): array => [
                                    $method->value =>
                                        $method->label(),
                                ]
                            )
                            ->all()
                    ),

                DatePicker::make(
                    'booking_date'
                )
                    ->label(
                        'Buchungsdatum'
                    )
                    ->required()
                    ->default(today()),

                DatePicker::make(
                    'value_date'
                )
                    ->label(
                        'Wertstellung'
                    ),

                TextInput::make(
                    'reference'
                )
                    ->label(
                        'Referenz'
                    )
                    ->maxLength(255),

                Textarea::make('notes')
                    ->label(
                        'Notiz'
                    )
                    ->rows(3),
            ])->action(
                static function (
                    array $data,
                    Member $record,
                    PaymentManager $manager,
                ): void {
                    Gate::authorize(
                        'managePayments',
                        $record
                    );

                    $user =
                        Auth::user();

                    abort_unless(
                        $user instanceof User,
                        403
                    );

                    $manager->create(
                        member:
                        $record,

                        amount:
                        (string) $data['amount'],

                        method:
                        PaymentMethod::from(
                            (string) $data['method']
                        ),

                        bookingDate:
                        CarbonImmutable::parse(
                            $data['booking_date']
                        ),

                        valueDate:
                        filled(
                            $data[
                            'value_date'
                            ] ?? null
                        )
                            ? CarbonImmutable::parse(
                            $data[
                            'value_date'
                            ]
                        )
                            : null,

                        reference:
                        filled(
                            $data[
                            'reference'
                            ] ?? null
                        )
                            ? (string) $data[
                        'reference'
                        ]
                            : null,

                        notes:
                        filled(
                            $data['notes']
                            ?? null
                        )
                            ? (string) $data[
                        'notes'
                        ]
                            : null,

                        createdBy:
                        $user,
                    );

                    Notification::make()
                        ->title(
                            'Zahlung gespeichert'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
