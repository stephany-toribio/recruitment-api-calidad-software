<?php

namespace App\Http\Requests\Vacantes;

/**
 * Edición de una vacante por su empresa (PUT): se envían todos los campos, con las mismas reglas y mensajes del registro.
 * Si la vacante está pagada, eliminada o no aprobada lo rechaza VacanteService (409), no esta validación.
 */
class ActualizarVacanteRequest extends RegistrarVacanteRequest {}
