<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordResetToken extends Model
{
    // Reglas del proceso de recuperación 
    public const CODIGO_MINUTOS = 15;          
    public const MAX_INTENTOS = 5;             
    public const ESPERA_REENVIO_SEGUNDOS = 60; 
    public const TOKEN_MINUTOS = 10;           

    protected $table = 'password_reset_tokens';

    protected $fillable = [
        'usuario_id',
        'codigo_hash',
        'intentos',
        'codigo_expira_en',
        'reset_token_hash',
        'reset_token_expira_en',
    ];

    // Los hashes nunca deben salir en una respuesta
    protected $hidden = [
        'codigo_hash',
        'reset_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'intentos'              => 'integer',
            'codigo_expira_en'      => 'datetime',
            'reset_token_expira_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /** El código aún sirve: no venció y no se agotaron los intentos */
    public function codigoVigente(): bool
    {
        return $this->codigo_expira_en->isFuture() && $this->intentos < self::MAX_INTENTOS;
    }

    /**
     * Ya pasó el tiempo mínimo desde el último envío.
     * El envío se deduce de codigo_expira_en (updated_at también cambia al fallar un intento).
     */
    public function puedeReenviar(): bool
    {
        return $this->codigo_expira_en->copy()
            ->subMinutes(self::CODIGO_MINUTOS)
            ->addSeconds(self::ESPERA_REENVIO_SEGUNDOS)
            ->isPast();
    }

    /** Hay un reset_token emitido y todavía no vence */
    public function resetTokenVigente(): bool
    {
        return $this->reset_token_hash !== null && $this->reset_token_expira_en?->isFuture() === true;
    }
}
