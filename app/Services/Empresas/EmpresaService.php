<?php

namespace App\Services\Empresas;

use App\Models\Empresa;
use App\Services\Auth\UsuarioService;
use Illuminate\Support\Facades\DB;

class EmpresaService
{
    public function __construct(
        private UsuarioService $usuarioService
    ) {
    }

    public function registrar(array $datos): Empresa
    {
        return DB::transaction(function () use ($datos) {
            $usuario = $this->usuarioService->registrar(
                [
                    'correo' => $datos['correo'],
                    'password' => $datos['password'],
                ],
                'empresa'
            );

            $empresa = Empresa::create([
                'usuario_id' => $usuario->id,
                'encargado_empresa' => $datos['encargado_empresa'],
                'razon_social' => $datos['razon_social'],
                'sector' => $datos['sector'],
                'descripcion' => $datos['descripcion'] ?? null,
                'ruc' => $datos['ruc'],
                'sitio_web' => $datos['sitio_web'] ?? null,
            ]);

            return $empresa->load('usuario');
        });
    }

    public function obtenerPorUsuario(int $usuarioId): Empresa
    {
        return Empresa::with('usuario')
            ->where('usuario_id', $usuarioId)
            ->firstOrFail();
    }

    public function actualizarPorUsuario(
        int $usuarioId,
        array $datos
    ): Empresa {
        $empresa = Empresa::where('usuario_id', $usuarioId)
            ->firstOrFail();

        $empresa->update([
            'encargado_empresa' => $datos['encargado_empresa'],
            'razon_social' => $datos['razon_social'],
            'sector' => $datos['sector'],
            'ruc' => $datos['ruc'],
            'sitio_web' => $datos['sitio_web'] ?? null,
            'descripcion' => $datos['descripcion'] ?? null,
        ]);

        return $empresa->load('usuario');
    }
}
