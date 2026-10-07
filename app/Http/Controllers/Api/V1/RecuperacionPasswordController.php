<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\RestablecerPasswordRequest;
use App\Http\Requests\Auth\VerificarCodigoRequest;
use App\Models\PasswordResetToken;
use App\Services\RecuperacionPassword\RecuperacionPasswordService;
use Illuminate\Http\JsonResponse;

/**
 * Recuperación de contraseña por código enviado al correo.
 */
class RecuperacionPasswordController extends BaseApiController
{
    public function __construct(private RecuperacionPasswordService $recuperacionService)
    {
    }

    /**
     * POST /api/v1/auth/forgot-password
     */
    public function solicitar(ForgotPasswordRequest $request): JsonResponse
    {
        $this->recuperacionService->solicitar($request->validated('correo'));

        return $this->successResponse(
            null,
            'Si el correo está registrado, te enviaremos un código para restablecer tu contraseña.'
        );
    }

    /**
     * POST /api/v1/auth/verify-reset-code
     */
    public function verificarCodigo(VerificarCodigoRequest $request): JsonResponse
    {
        $resetToken = $this->recuperacionService->verificarCodigo(
            $request->validated('correo'),
            $request->validated('codigo')
        );

        return $this->successResponse(
            [
                'reset_token'       => $resetToken,
                'expira_en_minutos' => PasswordResetToken::TOKEN_MINUTOS,
            ],
            'Código verificado correctamente.'
        );
    }

    /**
     * POST /api/v1/auth/reset-password
     */
    public function restablecer(RestablecerPasswordRequest $request): JsonResponse
    {
        $this->recuperacionService->restablecer(
            $request->validated('correo'),
            $request->validated('reset_token'),
            $request->validated('password')
        );

        return $this->successResponse(
            null,
            'Contraseña restablecida correctamente. Inicia sesión con tu nueva contraseña.'
        );
    }
}
