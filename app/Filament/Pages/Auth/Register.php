<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use SensitiveParameter;

final class Register extends BaseRegister
{
    protected function getEmailFormComponent(): Component
    {
        $component = parent::getEmailFormComponent();

        $invitationEmail = session(
            'pending_club_invitation_email'
        );

        if (
            is_string($invitationEmail)
            && $component instanceof TextInput
        ) {
            $component
                ->default($invitationEmail)
                ->readOnly();
        }

        return $component;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(
        #[SensitiveParameter]
        array $data,
    ): array {
        $invitationEmail = session(
            'pending_club_invitation_email'
        );

        if (
            is_string(
                $invitationEmail
            )
        ) {
            /*
             * Server-seitig überschreiben,
             * nicht nur das Feld sperren.
             */
            $data['email'] =
                mb_strtolower(
                    trim(
                        $invitationEmail
                    )
                );
        }

        return $data;
    }
}
