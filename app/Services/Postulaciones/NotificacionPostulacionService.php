<?php

namespace App\Services\Postulaciones;

use App\Models\Postulacion;

class NotificacionPostulacionService
{
    // Se llama después de confirmar la transacción, para que un fallo del correo no revierta el cambio de estado
    public function notificarDecision(Postulacion $postulacion): void
    {
        // Pendiente: enviar el correo al candidato y registrar el resultado del envío
    }
}
