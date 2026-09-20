<?php

namespace App\Filament\Pages;

use App\Application\Audit\AuditLogger;
use App\Domain\Audit\Enums\AuditAction;
use App\Filament\Actions\ChangePasswordAction;
use App\Filament\Actions\ResendEmailVerificationAction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

/**
 * @property-read Schema $form
 */
final class Profile extends Page
{
    protected static ?string $title = 'Profil';

    protected static ?string $navigationLabel = 'Profil';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'profile';

    protected string $view = 'filament.pages.profile';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('E-Mail-Adresse')
                    ->email()
                    ->required()
                    ->maxLength(255),
            ])
            ->statePath('data');
    }

    public function multiFactorAuthentication(Schema $schema): Schema
    {
        return $schema
            ->components(array_filter([
                $this->getMultiFactorAuthenticationContentComponent(),
            ]));
    }

    public function getMultiFactorAuthenticationContentComponent(): ?Component
    {
        if (! Filament::hasMultiFactorAuthentication()) {
            return null;
        }

        $user = Auth::user();

        return Section::make()
            ->label(__('filament-panels::auth/pages/edit-profile.multi_factor_authentication.label'))
            ->compact()
            ->divided()
            ->secondary()
            ->schema(collect(Filament::getMultiFactorAuthenticationProviders())
                ->sort(fn (MultiFactorAuthenticationProvider $multiFactorAuthenticationProvider): int => $multiFactorAuthenticationProvider->isEnabled($user) ? 0 : 1)
                ->map(fn (MultiFactorAuthenticationProvider $multiFactorAuthenticationProvider): Component => Group::make($multiFactorAuthenticationProvider->getManagementSchemaComponents())
                    ->statePath($multiFactorAuthenticationProvider->getId()))
                ->all());
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            ChangePasswordAction::make(),
            ResendEmailVerificationAction::make(),
        ];
    }

    public function saveProfile(AuditLogger $auditLogger): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $data = $this->form->getState();

        $originalEmail = $user->email;
        $originalName = $user->name;

        $user->name = $data['name'];
        $user->email = $data['email'];

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
            $user->sendEmailVerificationNotification();
        }

        $user->save();

        if ($user->wasChanged('name') || $user->wasChanged('email')) {
            $auditLogger->log(
                AuditAction::UserProfileChanged,
                $user,
                $user,
                [
                    'old' => [
                        'name' => $originalName,
                        'email' => $originalEmail,
                    ],
                    'new' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ]
            );
        }

        Notification::make()
            ->title('Profil gespeichert')
            ->success()
            ->send();
    }
}
