<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Candidatos\RegistrarCandidatoRequest;
use App\Http\Requests\Candidatos\ActualizarCandidatoRequest;
use App\Http\Resources\Api\V1\CandidatoResource;
use App\Services\CandidatoService; 
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Candidato;  

use Illuminate\Http\Request;

class CandidatoController extends BaseApiController
{
    public function __construct(private CandidatoService $candidatoService) {}


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


    // GET /api/v1/candidatos/{candidato} (Ver perfil por ID)
    public function show(Candidato $candidato): JsonResponse
    {
        $candidato->load(['habilidades', 'carreras', 'usuario']);
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

