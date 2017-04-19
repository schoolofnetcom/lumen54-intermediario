<?php

namespace App\Notifications;

use App\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountCreated extends Notification{
    /**
     * @var User
     */
    private $user;
    /**
     * @var
     */
    private $redirect;


    /**
     * AccountCreated constructor.
     */
    public function __construct(User $user, $redirect)
    {
        $this->user = $user;
        $this->redirect = $redirect;
    }

    public function via($notifiable){
        return ['mail'];
    }

    public function toMail($notifiable){
        return (new MailMessage())
            ->subject('Sua conta foi criada')
            ->greeting("Olá {$this->user->name},")
            ->line('Sua conta foi criada')
            ->action('Acesse este endereço para valida-la',$this->redirect)
            ->line('Obrigado por usar nossa aplicação')
            ->salutation('Atenciosamente,');
    }
}