<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordApi extends Notification implements ShouldQueue
{
    use Queueable;

    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        // Use Laravel web route for the reset password page
        $resetUrl = url('/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email));

        return (new MailMessage)
            ->subject('Atur Ulang Kata Sandi SAIBARA')
            ->line('Anda menerima email ini karena permintaan reset password untuk akun Anda.')
            ->action('Atur ulang kata sandi', $resetUrl)
            ->line('Tautan untuk atur ulang kata sandi anda akan kedaluwarsa dalam ' . config('auth.passwords.users.expire') . ' menit.')
            ->line('Jika anda tidak meminta reset password, tidak perlu tindakan lebih lanjut.');
    }
}
