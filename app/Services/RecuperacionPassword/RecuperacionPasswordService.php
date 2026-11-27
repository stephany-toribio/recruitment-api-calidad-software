<?php

namespace App\Services\RecuperacionPassword;

use App\Exceptions\BusinessException;
use App\Mail\CodigoRecuperacionMail;
use App\Models\PasswordResetToken;
use App\Models\Usuario;
use App\Services\Auth\UsuarioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Recuperación de contraseña por código enviado al correo 
 */
class RecuperacionPasswordService
{
    public function __construct(private UsuarioService $usuarioService)
    {
    }

    /**
     *  El controller responde lo mismo exista o no la cuenta
     */
    public function solicitar(string $correo): void
    {
        $usuario = Usuario::with(['candidato', 'empresa'])->where('correo', $correo)->first();

        // Sin cuenta o suspendida: no se envía nada
        if (!$usuario || $usuario->estado_cuenta === 'suspendido') {
            return;
        }

        // Reenvío antes de 60 s
        $proceso = PasswordResetToken::where('usuario_id', $usuario->id)->first();
        if ($proceso && !$proceso->puedeReenviar()) {
            return;
        }

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        try {
            // Si el correo no sale, se revierte el registro
            DB::transaction(function () use ($usuario, $codigo) {
                PasswordResetToken::updateOrCreate(
                    ['usuario_id' => $usuario->id],
                    [
                        'codigo_hash'           => Hash::make($codigo),
                        'intentos'              => 0,
                        'codigo_expira_en'      => now()->addMinutes(PasswordResetToken::CODIGO_MINUTOS),
                        'reset_token_hash'      => null,
                        'reset_token_expira_en' => null,
                    ]
                );

                Mail::to($usuario->correo)->send(new CodigoRecuperacionMail(
                    $codigo,
                    $this->nombreDe($usuario),
                    PasswordResetToken::CODIGO_MINUTOS
                ));
            });
        } catch (TransportExceptionInterface $e) {
            // Fallo de SMTP
            Log::error('No se pudo enviar el código de recuperación.', [
                'usuario_id' => $usuario->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Devuelve el reset_token en texto plano 
     */
    public function verificarCodigo(string $correo, string $codigo): string
    {
        $proceso = $this->procesoActivo($correo);

        // Mismo error para no existe expiro o sin intento
        if (!$proceso || !$proceso->codigoVigente()) {
            throw $this->codigoInvalido();
        }

        if (!Hash::check($codigo, $proceso->codigo_hash)) {
            $proceso->increment('intentos');
            throw $this->codigoInvalido();
        }

        $resetToken = Str::random(64);

        $proceso->update([
            // el código deja de servir apenas se verifica
            'codigo_expira_en'      => now(),
            // Token largo y aleatorio
            'reset_token_hash'      => hash('sha256', $resetToken),
            'reset_token_expira_en' => now()->addMinutes(PasswordResetToken::TOKEN_MINUTOS),
        ]);

        return $resetToken;
    }

    /**
     * Cambia la contraseña, cierra todas las sesiones y elimina el proceso 
     */
    public function restablecer(string $correo, string $resetToken, string $password): void
    {
        $proceso = $this->procesoActivo($correo);

        if (
            !$proceso
            || !$proceso->resetTokenVigente()
            || !hash_equals($proceso->reset_token_hash, hash('sha256', $resetToken))
        ) {
            throw new BusinessException(
                'El proceso de recuperación es inválido o ha expirado. Solicita un nuevo código.',
                422,
                'TOKEN_INVALIDO'
            );
        }

        DB::transaction(function () use ($proceso, $password) {
            // Guarda el hash de la nueva contraseña y revoca los tokens de Sanctum
            $this->usuarioService->actualizarPassword($proceso->usuario, $password);
            $proceso->delete();
        });
    }

    /** Proceso de un correo cuya cuenta sigue activa) */
    private function procesoActivo(string $correo): ?PasswordResetToken
    {
        return PasswordResetToken::with('usuario')
            ->whereHas('usuario', fn ($q) => $q->where('correo', $correo)->where('estado_cuenta', 'activo'))
            ->first();
    }

    private function codigoInvalido(): BusinessException
    {
        return new BusinessException('El código es inválido o ha expirado.', 422, 'CODIGO_INVALIDO');
    }

    /** Nombre para el saludo del correo: candidato, empresa o genérico */
    private function nombreDe(Usuario $usuario): string
    {
        return $usuario->candidato?->nombre ?? $usuario->empresa?->razon_social ?? 'usuario';
    }
}
