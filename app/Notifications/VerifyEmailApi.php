<?php

namespace App\Notifications;
// app/Notifications/VerifyEmailApi.php
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailApi extends Notification implements ShouldQueue
{
    use Queueable;
    public $tries = 3;
    public $timeout = 60;
    public $backoff = [10, 30, 60];

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function failed($exception = null)
    {
        \Log::error('Email verification notification failed', [
            'exception' => $exception ? $exception->getMessage() : 'Unknown error',
            'trace' => $exception ? $exception->getTraceAsString() : null
        ]);
    }

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verifikasi email')
            ->line('Tekan tombol dibawah untuk memverifikasi email Anda')
            ->action('Verifikasi Email', $verificationUrl)
            ->line('Jika Anda tidak membuat akun, tidak perlu tindakan lebih lanjut.');
    }

    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification())
            ]
        );
    }
}
