<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo al candidato cuando la empresa lo contrata desde el kanban.
 * Se envía de forma síncrona. 
 */
class ContratacionMail extends Mailable
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
            subject: '¡Fuiste seleccionado para '.$this->vacante.'!'
        );
    }

    public function content():Content
    {
        return new Content(
            view: 'emails.contratacion'
        );
    }
}