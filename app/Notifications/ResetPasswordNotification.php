<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email reset password berbahasa Indonesia. */
class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Reset Password - Green Plant')
            ->greeting('Halo '.($notifiable->nama ?? 'Pelanggan').',')
            ->line('Kami menerima permintaan untuk mengubah password akun Green Plant Anda.')
            ->action('Reset Password', $url)
            ->line('Tautan ini berlaku selama 60 menit. Abaikan email ini bila Anda tidak meminta reset password.')
            ->salutation('Salam, Green Plant');
    }
}
