<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso API para transformar los datos de un Candidato.
 *
 * REGLA DE NEGOCIO (RNF-06: Protección de datos de contacto):
 * - Los datos de contacto (correo, teléfono) son PRIVADOS.
 * - Solo se revelan si:
 *   a) El usuario autenticado es el mismo candidato dueño del perfil.
 *   b) El usuario autenticado es una empresa donde el candidato tiene una postulación activa.
 *   c) El usuario autenticado es administrador.
 */
class CandidatoResource extends JsonResource
{
    /**
     * Transforma el recurso en un array para Angular y el microservicio de Python.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        // En Sprint 1: implementar lógica de verificación según las relaciones de BD
        // Ejemplo: $puedeVerContacto = $user?->id === $this->user_id || $user?->esEmpresaConPostulacionDe($this->id);
        $puedeVerContacto = false;

        return [
            'id'          => $this->id,
            'nombre'      => $this->nombre,
            'correo'      => $puedeVerContacto ? $this->correo : null,
            'telefono'    => $puedeVerContacto ? $this->telefono : null,

            // Relación con habilidades (cargada condicionalmente cuando se use with('habilidades'))
            'habilidades' => HabilidadResource::collection($this->whenLoaded('habilidades')),

            // Metadatos adicionales para Sprint 1 (CV, nivel de compatibilidad con vacante, etc.)
            'creado_en'   => $this->created_at?->toISOString(),
        ];
    }
}
