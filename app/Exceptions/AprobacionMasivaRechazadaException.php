<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * MOD-04 "todo o nada": alguna vacante del lote no se puede aprobar, así que no se aprueba ninguna (HTTP 422).
 * A diferencia de BusinessException, la respuesta incluye `errors` con la causa de cada vacante.
 * Laravel usa este render() antes que el de BusinessException registrado en bootstrap/app.php.
 */
class AprobacionMasivaRechazadaException extends BusinessException
{
    /**
     * @param  array<string, array<int, string>>  $errores  p. ej. ['vacantes.2' => ['La vacante 18 ya fue revisada.']]
     */
    public function __construct(private array $errores)
    {
        parent::__construct(
            'No se aprobó ninguna vacante: hay vacantes que no se pueden aprobar.',
            422,
            'BULK_APPROVAL_REJECTED'
        );
    }

    public function render(Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->getErrorCode(),
            'errors' => $this->errores,
        ], $this->getStatusCode());
    }
}
