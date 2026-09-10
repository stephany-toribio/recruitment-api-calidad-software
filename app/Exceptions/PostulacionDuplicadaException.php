<?php

namespace App\Exceptions;

/**
 * Excepción de regla de negocio: Postulación Duplicada (HTTP 409 Conflict).
 *
 * Se dispara cuando un candidato intenta postular a una vacante en la que
 * ya tiene un registro de postulación activo.
 *
 * Ejemplo de uso en Sprint 1:
 *   if ($candidato->yaPostulo($vacante->id)) {
 *       throw new PostulacionDuplicadaException();
 *   }
 */
class PostulacionDuplicadaException extends BusinessException
{
    public function __construct(
        string $message = 'Ya te has postulado previamente a esta vacante laboral.',
        int $statusCode = 409,
        string $errorCode = 'DUPLICATE_APPLICATION'
    ) {
        parent::__construct($message, $statusCode, $errorCode);
    }
}
