<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Usuario;
use App\Services\Admin\UsuarioAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsuarioController extends BaseApiController
{
    public function index(
        UsuarioAdminService $usuarioAdminService
    ): JsonResponse {
        $usuarios = $usuarioAdminService->listar();

        return $this->successResponse(
            $usuarios,
            'Usuarios obtenidos correctamente.'
        );
    }

    public function suspender(
        Request $request,
        Usuario $usuario,
        UsuarioAdminService $usuarioAdminService
    ): JsonResponse {
        $request->validate([
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $usuario = $usuarioAdminService->suspender(
            $usuario,
            $request->input('motivo')
        );

        return $this->successResponse(
            $usuario,
            'Cuenta suspendida correctamente.'
        );
    }

    public function reactivar(
        Usuario $usuario,
        UsuarioAdminService $usuarioAdminService
    ): JsonResponse {
        $usuario = $usuarioAdminService->reactivar($usuario);

        return $this->successResponse(
            $usuario,
            'Cuenta reactivada correctamente.'
        );
    }
}