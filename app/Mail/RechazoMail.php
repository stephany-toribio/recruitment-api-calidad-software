<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo al candidato cuando la empresa lo rechaza desde el kanban. No incluye el motivo.
 * Se envía de forma síncrona. 
 */
class RechazoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public string $vacante,
        public string $empresa
    ) {}

    public function envelope():Envelope
    {
        return new Envelope(
            subject: 'Información sobre tu postulación a: '.$this->vacante, 
        );
    }

    public function content():Content
    {
        return new Content(
            view: 'emails.rechazo'
        );
    }
}