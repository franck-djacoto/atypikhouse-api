<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationPaid extends Notification
{
    use Queueable;
    private $factureName;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(String $reservationFactureName)
    {
        $this->factureName = $reservationFactureName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->from(env('NOTIFICATION_MAIL_CONTACT'))
                    ->subject('Votre facture AtypikHouse')
                    ->greeting('Bonjour,')
                    ->line('Veuillez trouver joint à ce mail votre facture. N\'hésistez pas à nous contacter en cas de réclamation.')
                    ->line('Merci de faire confiance à AtypikHouse !')
                    ->attach(public_path().'/storage/facture_reservations/'.$this->factureName,
                    ['as' => 'facture.pdf', 'mime' => 'application/pdf',]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
