<?php

namespace App\Services\Catalogos;

use App\Models\Idioma;
use App\Models\Institucion;
use App\Models\Organizacion;
use App\Models\Pais;
use App\Models\Puesto;
use App\Models\TipoEstudio;
use Illuminate\Database\Eloquent\Collection;

// Catálogos públicos del registro: solo devuelve lo que el candidato puede seleccionar
class CatalogoService
{
    public function paises(): Collection
    {
        return Pais::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);
    }

    // Orden de creación: Universitario, Terciario, Posgrado, Master, Doctorado, Secundario
    public function tiposEstudio(): Collection
    {
        return TipoEstudio::orderBy('id')->get(['id', 'nombre']);
    }

    public function instituciones(?int $paisId): Collection
    {
        return Institucion::where('estado', 'activo')
            ->whereHas('pais', fn ($query) => $query->where('activo', true))
            ->when($paisId, fn ($query) => $query->where('pais_id', $paisId))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'pais_id']);
    }

    public function idiomas(): Collection
    {
        return Idioma::orderBy('nombre')->get(['id', 'nombre']);
    }

    // Solo los globales habilitados: los personalizados que escribe cada candidato no se muestran a otros
    public function puestos(): Collection
    {
        return Puesto::whereNull('candidato_id')
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
    }

    // Para el candidato autenticado: los globales habilitados más sus propios personalizados habilitados (nunca los de otros)
    public function puestosParaCandidato(int $candidatoId): Collection
    {
        return Puesto::where('estado', 'activo')
            ->where(fn ($query) => $query->whereNull('candidato_id')->orWhere('candidato_id', $candidatoId))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'candidato_id']);
    }

    public function organizaciones(?int $paisId): Collection
    {
        return Organizacion::where('estado', 'activo')
            ->where('estado_catalogo', 'aprobado')
            ->when($paisId, fn ($query) => $query->where('pais_id', $paisId))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'pais_id']);
    }
}
