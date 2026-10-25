<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessException;
use App\Http\Requests\Postulaciones\PostularVacanteRequest;
use App\Http\Resources\Api\V1\PostulacionCreadaResource;
use App\Services\Postulaciones\PostulacionService;
use Illuminate\Http\JsonResponse;

class PostulacionController extends BaseApiController
{
    public function __construct(
        private PostulacionService $postulaciones
    ) {}

    public function store(
        PostularVacanteRequest $request
    ): JsonResponse {
        $candidato = $request->user()?->candidato;

        if ($candidato === null) {
            throw new BusinessException(
                'No tienes permisos para realizar esta acción.',
                403,
                'FORBIDDEN'
            );
        }

        $datos = $request->validated();

        $postulacion = $this->postulaciones->postular(
            $candidato,
            (int) $datos['vacante_id']
        );

        return $this->successResponse(
            (new PostulacionCreadaResource($postulacion))
                ->resolve($request),
            'Postulación registrada.',
            201
        );
    }
}
