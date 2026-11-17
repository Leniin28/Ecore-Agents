<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject('Restablecer contraseña | ECore Agents')
            ->greeting('Hola')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
            ->line('Tu código de verificación es: '.$this->token)
            ->line('El código vence en 15 minutos y puede usarse una sola vez.')
            ->line('Si no solicitaste este cambio, ignora este mensaje.');
    }
}
