<?php

namespace App\Filament\Resources\Members\Schemas;

use App\Application\Contribution\PaymentBalanceResolver;
use App\Domain\Contribution\Enums\ChargePaymentState;
use App\Domain\Contribution\Enums\PaymentMethod;
use App\Domain\Contribution\Enums\PaymentStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Membership\Enums\MemberDocumentType;
use App\Domain\Membership\Enums\MembershipStatus;
use App\Domain\Membership\Models\MemberDocument;
use App\Filament\Resources\Members\Actions\AllocatePaymentAction;
use App\Filament\Resources\Members\Actions\AssignFunctionAction;
use App\Filament\Resources\Members\Actions\ChangeAddressAction;
use App\Filament\Resources\Members\Actions\ChangeContactDataAction;
use App\Filament\Resources\Members\Actions\ChangeMembershipTypeAction;
use App\Filament\Resources\Members\Actions\ChangePersonalDataAction;
use App\Filament\Resources\Members\Actions\CreateSepaMandateAction;
use App\Filament\Resources\Members\Actions\EndFunctionAction;
use App\Filament\Resources\Members\Actions\JoinDepartmentAction;
use App\Filament\Resources\Members\Actions\LeaveDepartmentAction;
use App\Filament\Resources\Members\Actions\LeaveMemberAction;
use App\Filament\Resources\Members\Actions\ReactivateMemberAction;
use App\Filament\Resources\Members\Actions\SuspendMemberAction;
use App\Filament\Resources\Members\Actions\UploadDocumentAction;
use Filament\Actions\ActionGroup;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;

class MemberInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['lg' => 2])
            ->components([
                Section::make('Mitgliedschaft')
                    ->key('membership')
                    ->headerActions([
                        ActionGroup::make([
                            ChangeMembershipTypeAction::make(),
                            SuspendMemberAction::make(),
                            ReactivateMemberAction::make(),
                            LeaveMemberAction::make(),
                        ])->label('Aktionen')->icon('heroicon-o-ellipsis-horizontal')->button(),
                    ])
                    ->icon('heroicon-o-identification')
                    ->columnSpanFull()
                    ->columns(['sm' => 2, 'xl' => 4])
                    ->schema([
                        TextEntry::make('member_number')->label('Mitgliedsnummer'),
                        TextEntry::make('membershipType.name')
                            ->label('Mitgliedsart')
                            ->placeholder('Keine Mitgliedsart'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(
                                static fn (
                                    MembershipStatus $state,
                                ): string => match ($state) {
                                    MembershipStatus::Active => 'Aktiv',

                                    MembershipStatus::Suspended => 'Gesperrt',

                                    MembershipStatus::Left => 'Ausgetreten',
                                }
                            )
                            ->color(
                                static fn (
                                    MembershipStatus $state,
                                ): string => match ($state) {
                                    MembershipStatus::Active => 'success',

                                    MembershipStatus::Suspended => 'warning',

                                    MembershipStatus::Left => 'danger',
                                }
                            ),
                        TextEntry::make('club.name')->label('Verein'),
                        TextEntry::make('joined_at')->label('Eintritt')->date('d.m.Y')->placeholder('–'),
                        TextEntry::make('left_at')->label('Austritt')->date('d.m.Y')->placeholder('–'),
                    ]),
                Section::make('Persönliche Daten')
                    ->key('personal')
                    ->headerActions([
                        ChangePersonalDataAction::make()->label('Bearbeiten'),
                    ])
                    ->icon('heroicon-o-user')
                    ->columns(['sm' => 2])
                    ->schema([
                        TextEntry::make('first_name')->label('Vorname'),
                        TextEntry::make('last_name')->label('Nachname'),
                        TextEntry::make('birth_date')->label('Geburtsdatum')->date('d.m.Y')->placeholder('–'),
                    ]),
                Section::make('Kontakt')
                    ->key('contact')
                    ->headerActions([
                        ChangeContactDataAction::make()->label('Bearbeiten'),
                    ])
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->columns(['sm' => 2])
                    ->schema([
                        TextEntry::make('email')->label('E-Mail')->placeholder('–')->columnSpanFull(),
                        TextEntry::make('phone')->label('Telefon')->placeholder('–'),
                        TextEntry::make('mobile')->label('Mobiltelefon')->placeholder('–'),
                    ]),
                Section::make('Anschrift')
                    ->key('address')
                    ->headerActions([
                        ChangeAddressAction::make()->label('Bearbeiten'),
                    ])
                    ->icon('heroicon-o-map-pin')
                    ->columnSpanFull()
                    ->columns(['sm' => 2, 'xl' => 5])
                    ->schema([
                        TextEntry::make('street')->label('Straße')->placeholder('–'),
                        TextEntry::make('house_number')->label('Hausnummer')->placeholder('–'),
                        TextEntry::make('postal_code')->label('Postleitzahl')->placeholder('–'),
                        TextEntry::make('city')->label('Ort')->placeholder('–'),
                        TextEntry::make('country_code')->label('Land')->placeholder('–'),
                    ]),
                Section::make('Abteilungen')
                    ->key('departments')
                    ->headerActions([
                        ActionGroup::make([
                            JoinDepartmentAction::make(),
                            LeaveDepartmentAction::make(),
                        ])->label('Aktionen')->icon('heroicon-o-ellipsis-horizontal')->button(),
                    ])
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        RepeatableEntry::make('departments')
                            ->placeholder('Keine Abteilungen zugeordnet')
                            ->label('')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Abteilung'),
                            ])
                            ->columns(1),
                    ]),
                Section::make('Vereinsfunktionen')
                    ->key('functions')
                    ->headerActions([
                        ActionGroup::make([
                            AssignFunctionAction::make(),
                            EndFunctionAction::make(),
                        ])->label('Aktionen')->icon('heroicon-o-ellipsis-horizontal')->button(),
                    ])
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        RepeatableEntry::make(
                            'activeFunctionAssignments'
                        )
                            ->placeholder('Keine aktiven Funktionen')
                            ->label('')
                            ->schema([
                                TextEntry::make(
                                    'clubFunction.name'
                                )
                                    ->label('Funktion'),

                                TextEntry::make('valid_from')
                                    ->label('Seit')
                                    ->date('d.m.Y'),
                            ])
                            ->columns(2),
                    ]),
                Section::make('Dokumente')
                    ->key('documents')
                    ->headerActions([
                        UploadDocumentAction::make(),
                    ])
                    ->icon('heroicon-o-document-text')
                    ->columnSpanFull()
                    ->description(
                        'Hochgeladene Dokumente dieses Mitglieds.'
                    )
                    ->schema([
                        RepeatableEntry::make('documents')
                            ->label('')
                            ->placeholder('Keine Dokumente vorhanden')
                            ->schema([
                                TextEntry::make('original_name')
                                    ->label('Dateiname')
                                    ->icon('heroicon-o-arrow-down-tray')
                                    ->url(static fn (MemberDocument $record): ?string => Gate::allows('viewDocuments', $record->member)
                                        ? route('member-documents.download', $record)
                                        : null),

                                TextEntry::make('type')
                                    ->label('Dokumenttyp')
                                    ->formatStateUsing(
                                        static function (
                                            MemberDocumentType|string|null $state
                                        ): string {
                                            if ($state instanceof MemberDocumentType) {
                                                return $state->label();
                                            }

                                            if (is_string($state)) {
                                                return MemberDocumentType::tryFrom($state)
                                                    ?->label()
                                                    ?? $state;
                                            }

                                            return '—';
                                        }
                                    ),

                                TextEntry::make('mime_type')
                                    ->label('Dateityp'),

                                TextEntry::make('size')
                                    ->label('Größe')
                                    ->formatStateUsing(
                                        static fn (
                                            int|string|null $state
                                        ): string => Number::fileSize(
                                            (int) $state
                                        )
                                    ),

                                TextEntry::make('created_at')
                                    ->label('Hochgeladen am')
                                    ->dateTime(
                                        'd.m.Y H:i'
                                    ),
                            ])
                            ->columns(3),
                    ]),
                Section::make('Funktionshistorie')
                    ->icon('heroicon-o-clock')
                    ->columnSpanFull()
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make(
                            'functionAssignments'
                        )
                            ->placeholder('Keine bisherigen Funktionen')
                            ->label('')
                            ->schema([
                                TextEntry::make(
                                    'clubFunction.name'
                                )
                                    ->label('Funktion'),

                                TextEntry::make('valid_from')
                                    ->label('Von')
                                    ->date('d.m.Y'),

                                TextEntry::make('valid_until')
                                    ->label('Bis')
                                    ->date('d.m.Y')
                                    ->placeholder('heute'),
                            ])
                            ->columns(3),
                    ]),
                Section::make('Beitragsforderungen')
                    ->schema([
                        RepeatableEntry::make(
                            'contributionCharges'
                        )
                            ->label('')
                            ->schema([
                                TextEntry::make(
                                    'description'
                                )
                                    ->label(
                                        'Beschreibung'
                                    ),

                                TextEntry::make(
                                    'contributionType.name'
                                )
                                    ->label(
                                        'Beitragsart'
                                    ),

                                TextEntry::make(
                                    'amount'
                                )
                                    ->label(
                                        'Forderungsbetrag'
                                    )
                                    ->money('EUR'),

                                TextEntry::make('allocated_amount')
                                    ->label('Bezahlt')
                                    ->state(
                                        static fn (ContributionCharge $record): string => app(PaymentBalanceResolver::class)->allocatedAmount($record)
                                    )
                                    ->money('EUR'),

                                TextEntry::make('outstanding_amount')
                                    ->label('Offen')
                                    ->state(
                                        static fn (ContributionCharge $record): string => app(PaymentBalanceResolver::class)->outstandingAmount($record)
                                    )
                                    ->money('EUR'),

                                TextEntry::make('payment_state')
                                    ->label('Status')
                                    ->badge()
                                    ->state(
                                        static fn (ContributionCharge $record): ChargePaymentState => app(PaymentBalanceResolver::class)->state($record)
                                    )
                                    ->formatStateUsing(
                                        static fn (
                                            ChargePaymentState $state
                                        ): string => $state->label()
                                    )
                                    ->color(
                                        static fn (
                                            ChargePaymentState $state
                                        ): string => match ($state) {
                                            ChargePaymentState::Open => 'warning',

                                            ChargePaymentState::PartiallyPaid => 'warning',

                                            ChargePaymentState::Paid => 'success',
                                        }
                                    ),

                                TextEntry::make(
                                    'period_from'
                                )
                                    ->label(
                                        'Von'
                                    )
                                    ->date(
                                        'd.m.Y'
                                    ),

                                TextEntry::make(
                                    'period_until'
                                )
                                    ->label(
                                        'Bis'
                                    )
                                    ->date(
                                        'd.m.Y'
                                    )
                                    ->placeholder(
                                        '—'
                                    ),

                                TextEntry::make(
                                    'due_date'
                                )
                                    ->label(
                                        'Fällig'
                                    )
                                    ->date(
                                        'd.m.Y'
                                    ),

                                TextEntry::make('paid_at')
                                    ->label('Bezahlt am')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('—'),

                                TextEntry::make('cancelled_at')
                                    ->label('Storniert am')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('—'),

                                TextEntry::make('cancellation_reason')
                                    ->label('Stornogrund')
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                            ])
                            ->columns(4),
                    ]),
                Section::make('Zahlungen')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('payments')
                            ->label('')
                            ->placeholder('Keine Zahlungen vorhanden')
                            ->schema([
                                TextEntry::make('booking_date')
                                    ->label('Datum')
                                    ->date('d.m.Y'),

                                TextEntry::make('amount')
                                    ->label('Betrag')
                                    ->money('EUR'),

                                TextEntry::make('method')
                                    ->label('Zahlungsart')
                                    ->formatStateUsing(
                                        static fn (PaymentMethod $state): string => $state->label()
                                    ),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(
                                        static fn (PaymentStatus $state): string => $state->label()
                                    ),

                                TextEntry::make('reference')
                                    ->label('Referenz')
                                    ->placeholder('—'),

                                Actions::make([
                                    AllocatePaymentAction::make(),
                                ])
                                    ->key('allocation')
                                    ->columnSpanFull(),
                            ])
                            ->columns(5),
                    ]),
                Section::make('SEPA-Mandat')
                    ->key('sepaMandate')
                    ->headerActions([
                        CreateSepaMandateAction::make(),
                    ])
                    ->schema([
                        TextEntry::make(
                            'activeSepaMandate.mandate_reference'
                        )
                            ->label(
                                'Mandatsreferenz'
                            ),

                        TextEntry::make(
                            'activeSepaMandate.account_holder'
                        )
                            ->label(
                                'Kontoinhaber'
                            ),

                        TextEntry::make(
                            'activeSepaMandate.iban'
                        )
                            ->label('IBAN')
                            ->formatStateUsing(
                                static function (
                                    ?string $state
                                ): string {
                                    if ($state === null) {
                                        return '—';
                                    }

                                    $lastFour =
                                        substr(
                                            $state,
                                            -4
                                        );

                                    return '•••• •••• •••• '
                                        .$lastFour;
                                }
                            ),

                        TextEntry::make(
                            'activeSepaMandate.signed_at'
                        )
                            ->label(
                                'Mandat erteilt'
                            )
                            ->date('d.m.Y'),

                        TextEntry::make(
                            'activeSepaMandate.status'
                        )
                            ->label('Status')
                            ->badge(),
                    ]),
                Section::make('Systeminformationen')
                    ->icon('heroicon-o-information-circle')
                    ->columnSpanFull()
                    ->collapsed()
                    ->columns(['sm' => 2])
                    ->schema([
                        TextEntry::make('uuid')->label('UUID')->columnSpanFull(),
                        TextEntry::make('created_at')->label('Erstellt am')->dateTime('d.m.Y H:i')->placeholder('–'),
                        TextEntry::make('updated_at')->label('Zuletzt geändert')->dateTime('d.m.Y H:i')->placeholder('–'),
                    ]),
            ]);
    }
}
