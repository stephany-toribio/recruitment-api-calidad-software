<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;

class AuthController extends BaseApiController
{
    /**
     * Endpoint simulado (Mock) de Login para pruebas en Postman
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Datos sinteticos 
        $fakeToken = 'token-falso-de-prueba-123456789';
        $fakeUser = [
            'id'     => 1,
            'nombre' => 'Candidato de Pruebaaa',
            'email'  => $request->input('email'),
            'rol'    => 'candidato',
        ];

        return $this->successResponse([
            'token' => $fakeToken,
            'user'  => $fakeUser,
        ], 'Inicio de sesión simulado exitoso');
    }
}
