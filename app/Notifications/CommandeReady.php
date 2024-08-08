<?php

namespace App\Notifications;

use App\Models\Commande;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommandeReady extends Notification
{
    use Queueable;

    private $commande;

    /**
     * Create a new notification instance.
     */
    public function __construct(Commande $commande)
    {
        $this->commande = $commande;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('Votre commande est prete.')
                    ->action('Voir la commande', url('/commandes/' . $this->commande->id))
                    ->attachData($this->generatePdf(), 'facture.pdf',[
                        'mime' => 'application/pdf',
                    ])
                    ->line('Thank you for using our application!');
    }
    private function generatePdf(){
        //recuperer les details de la commande
        $commande = $this->commande;
        $details = $commande->details_commandes;

        //Charger la vue pour la vue pour le PDF
        $pdf = PDF::loadView('pdf.facture',[
            'commande' => $commande,
            'details' => $details,
        ]);

        //retourner le pdf en tant que string
        return $pdf->output();
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
