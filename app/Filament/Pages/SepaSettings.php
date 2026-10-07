<?php

namespace App\Filament\Pages;

use App\Application\Club\CurrentClub;
use App\Application\Sepa\ClubSepaConfigurationManager;
use App\Domain\Identity\Enums\Permission;
use App\Models\User;
use Auth;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * @property-read Schema $form
 */
final class SepaSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static string|UnitEnum|null $navigationGroup = 'Einstellungen';

    protected static ?string $navigationLabel = 'SEPA';

    protected static ?string $title = 'SEPA-Konfiguration';

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.pages.sepa-settings';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(): void
    {
        $configuration =
            app(CurrentClub::class)
                ->get()
                ->sepaConfiguration;

        if ($configuration === null) {
            $this->form->fill([
                'default_lead_days' => 5,
                'is_active' => true,
            ]);

            return;
        }

        $this->form->fill([
            'creditor_identifier' => $configuration
                ->creditor_identifier,

            'account_holder' => $configuration
                ->account_holder,

            /*
             * Da das Model encrypted cast nutzt,
             * bekommen wir hier bereits Klartext.
             */
            'iban' => $configuration->iban,

            'bic' => $configuration->bic,

            'mandate_reference_prefix' => $configuration
                ->mandate_reference_prefix,

            'default_lead_days' => $configuration
                ->default_lead_days,

            'default_purpose' => $configuration
                ->default_purpose,

            'is_active' => $configuration
                ->is_active,
        ]);
    }

    public function form(
        Schema $schema,
    ): Schema {
        return $schema
            ->components([
                TextInput::make(
                    'creditor_identifier'
                )
                    ->label(
                        'Gläubiger-ID'
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
                    ->maxLength(50),

                TextInput::make('bic')
                    ->label('BIC')
                    ->maxLength(20),

                TextInput::make(
                    'mandate_reference_prefix'
                )
                    ->label(
                        'Mandatspräfix'
                    )
                    ->maxLength(30)
                    ->helperText(
                        'Beispiel: SV- oder M-'
                    ),

                TextInput::make(
                    'default_lead_days'
                )
                    ->label(
                        'Standard-Vorlauftage'
                    )
                    ->integer()
                    ->minValue(0)
                    ->maxValue(30)
                    ->required()
                    ->default(5),

                Textarea::make(
                    'default_purpose'
                )
                    ->label(
                        'Standard-Verwendungszweck'
                    )
                    ->rows(3)
                    ->maxLength(140),

                Toggle::make('is_active')
                    ->label(
                        'SEPA-Lastschriften aktiv'
                    )
                    ->default(true),
            ])
            ->statePath('data');
    }

    public function save(
        ClubSepaConfigurationManager $manager,
    ): void {
        $user =
            Auth::user();

        abort_unless($user instanceof User, 403);

        abort_unless(
            $user->can(
                Permission::SepaConfigurationManage
                    ->value
            ),
            403
        );

        Gate::authorize(
            Permission::SepaConfigurationManage
                ->value
        );

        $data =
            $this->form
                ->getState();

        $manager->save(
            creditorIdentifier: (string) $data[
            'creditor_identifier'
            ],

            accountHolder: (string) $data[
            'account_holder'
            ],

            iban: (string) $data['iban'],

            bic: filled(
                $data['bic'] ?? null
            )
                ? (string) $data['bic']
                : null,

            mandateReferencePrefix: filled(
                $data[
                'mandate_reference_prefix'
                ] ?? null
            )
                ? (string) $data[
            'mandate_reference_prefix'
            ]
                : null,

            defaultLeadDays: (int) $data[
            'default_lead_days'
            ],

            defaultPurpose: filled(
                $data[
                'default_purpose'
                ] ?? null
            )
                ? (string) $data[
            'default_purpose'
            ]
                : null,

            isActive: (bool) $data[
            'is_active'
            ],

            user: $user,
        );

        Notification::make()
            ->title(
                'SEPA-Konfiguration gespeichert'
            )
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user !== null
            && $user->can(
                Permission::SepaConfigurationView
                    ->value
            );
    }
}
