<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Excepción base para reglas de negocio de la aplicación.
 *
 * Permite que cualquier desarrollador lance una excepción con:
 * - Mensaje amigable en español.
 * - Código de estado HTTP adecuado (409 Conflict, 422 Unprocessable, etc.).
 * - Un código de error estructurado ('error_code') para que Angular o Python puedan
 *   identificar el problema sin tener que analizar texto plano.
 *
 * Ejemplo de uso en controladores o servicios:
 *   throw new BusinessException('La vacante se encuentra cerrada.', 422, 'VACANCY_CLOSED');
 */
class BusinessException extends Exception
{
    protected string $errorCode;
    protected int $statusCode;

    public function __construct(
        string $message = 'Error en la operación de negocio.',
        int $statusCode = 422,
        string $errorCode = 'BUSINESS_RULE_ERROR',
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
