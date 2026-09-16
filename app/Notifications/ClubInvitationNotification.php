<?php

namespace App\Notifications;

use App\Domain\Club\Models\ClubInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ClubInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ClubInvitation $invitation,
        private readonly string $token,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(
        object $notifiable,
    ): array {
        return [
            'mail',
        ];
    }

    public function toMail(
        object $notifiable,
    ): MailMessage {
        $url = route(
            'club-invitations.show',
            [
                'token' => $this->token,
            ],
        );

        return (new MailMessage)
            ->subject(
                'Einladung zu '
                .$this->invitation
                    ->club
                    ->name
            )
            ->greeting('Hallo!')
            ->line(
                'Du wurdest eingeladen, '
                .'dem Verein '
                .$this->invitation
                    ->club
                    ->name
                .' beizutreten.'
            )
            ->line(
                'Die Einladung ist bis '
                .$this->invitation
                    ->expires_at
                    ->format('d.m.Y H:i')
                .' gültig.'
            )
            ->action(
                'Einladung öffnen',
                $url,
            )
            ->line(
                'Falls du diese Einladung nicht erwartet hast, kannst du diese E-Mail ignorieren.'
            );
    }
}
