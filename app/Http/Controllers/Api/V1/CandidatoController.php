<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Candidatos\RegistrarCandidatoRequest;
use App\Http\Requests\Candidatos\ActualizarCandidatoRequest;
use App\Http\Requests\Catalogos\FiltrarPorPaisRequest;
use App\Http\Resources\Api\V1\CandidatoResource;
use App\Http\Resources\Api\V1\CatalogoResource;
use App\Services\CandidatoService;
use App\Services\Catalogos\CatalogoService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Candidato;  

use Illuminate\Http\Request;

class CandidatoController extends BaseApiController
{
    public function __construct(
        private CandidatoService $candidatoService,
        private CatalogoService $catalogoService,
    ) {}


    // POST /api/v1/candidatos (Crear)
    public function store(RegistrarCandidatoRequest $request): JsonResponse
    {
        $candidato = $this->candidatoService->registrar($request->validated());
        return $this->successResponse(
            new CandidatoResource($candidato),
            'Candidato registrado exitosamente',
            Response::HTTP_CREATED
        ); 
    }


    // GET /api/v1/candidatos/me (Ver perfil del candidato autenticado)
    public function me(Request $request): JsonResponse
    {
        $candidato = $this->candidatoService->obtenerPorUsuario($request->user()->id);

        return $this->successResponse(
            new CandidatoResource($candidato),
            'Perfil de candidato obtenido exitosamente'
        );
    }


    // GET /api/v1/candidatos/me/puestos (Puestos que puede elegir: catálogo global + sus personalizados)
    public function puestos(Request $request): JsonResponse
    {
        $candidato = $this->candidatoService->obtenerPorUsuario($request->user()->id);

        return $this->successResponse(
            CatalogoResource::collection($this->catalogoService->puestosParaCandidato($candidato->id)),
            'Puestos disponibles obtenidos correctamente'
        );
    }


    // GET /api/v1/candidatos/me/niveles-puesto Niveles que puede elegir catálogo global + sus personalizados
    public function nivelesPuesto(Request $request): JsonResponse
    {
        $candidato = $this->candidatoService->obtenerPorUsuario($request->user()->id);

        return $this->successResponse(
            CatalogoResource::collection($this->catalogoService->nivelesPuestoParaCandidato($candidato->id)),
            'Niveles del puesto disponibles obtenidos correctamente'
        );
    }


    // GET /api/v1/candidatos/me/organizaciones?pais_id= Organizaciones que puede elegir: catálogo global + sus personalizadas
    public function organizaciones(FiltrarPorPaisRequest $request): JsonResponse
    {
        $candidato = $this->candidatoService->obtenerPorUsuario($request->user()->id);

        return $this->successResponse(
            CatalogoResource::collection(
                $this->catalogoService->organizacionesParaCandidato($candidato->id, $request->validated('pais_id'))
            ),
            'Organizaciones disponibles obtenidas correctamente'
        );
    }


    // GET /api/v1/candidatos/{candidato} (Ver perfil por ID)
    public function show(Request $request, Candidato $candidato): JsonResponse
    {
        if ($request->user()->rol?->nombre !== 'admin') {
            $empresaId = $request->user()->empresa?->id;

            $tieneVinculo = $empresaId !== null
                && \App\Models\Postulacion::query()
                    ->where('candidato_id', $candidato->id)
                    ->whereNull('retirada_en')
                    ->whereHas('empleo', function ($query) use ($empresaId) {
                        $query->where('empresa_id', $empresaId)
                            ->whereNull('empleos.deleted_at');
                    })
                    ->exists();

            if (! $tieneVinculo) {
                throw new \App\Exceptions\BusinessException(
                    'No tienes permisos para realizar esta acción.',
                    403,
                    'FORBIDDEN'
                );
            }
        }

        $candidato = $this->candidatoService->cargarPerfil($candidato);

        return $this->successResponse(
            new CandidatoResource($candidato),
            'Perfil de candidato obtenido exitosamente'
        );
    }


    // PUT /api/v1/candidatos/me (Actualizar perfil del candidato autenticado)
    public function update(ActualizarCandidatoRequest $request): JsonResponse
    {
        $candidato = $this->candidatoService->obtenerPorUsuario($request->user()->id);

        $candidatoActualizado = $this->candidatoService->actualizar(
            $candidato,
            $request->validated()
        ); 
        
        return $this->successResponse(
            new CandidatoResource($candidatoActualizado),
            'Perfil actualizado exitosamente'
        );
    } 
}

