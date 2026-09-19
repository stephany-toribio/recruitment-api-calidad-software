<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'usuarios';

    protected $fillable = [
        'correo',
        'password',
        'rol_id',
        'estado_cuenta',
        'intentos_fallidos',
        'bloqueado_hasta',
        'motivo_suspension',
        'fecha_suspension',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'bloqueado_hasta' => 'datetime',
            'fecha_suspension' => 'datetime',
        ];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function candidato(): HasOne
    {
        return $this->hasOne(Candidato::class, 'usuario_id');
    }

    public function empresa(): HasOne
    {
        return $this->hasOne(Empresa::class, 'usuario_id');
    }
}