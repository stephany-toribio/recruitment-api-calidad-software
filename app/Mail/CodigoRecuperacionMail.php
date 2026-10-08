<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo con el código de 6 dígitos para restablecer la contraseña
 * Se envía de forma síncrona 
 */
class CodigoRecuperacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $codigo,
        public string $nombre,
        public int $minutos,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu código para restablecer la contraseña',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.codigo-recuperacion',
        );
    }
}
