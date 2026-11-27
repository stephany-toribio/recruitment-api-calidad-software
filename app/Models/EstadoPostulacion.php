<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoPostulacion extends Model
{
    // Nombres exactos del catálogo sembrado
    public const RECIBIDO = 'Recibido';

    public const EN_REVISION = 'En Revisión';

    public const ENTREVISTA = 'Entrevista';

    public const CONTRATADO = 'Contratado';

    public const RECHAZADO = 'Rechazado';

    // Orden de las etapas del proceso de selección
    public const ORDEN = [
        self::RECIBIDO,
        self::EN_REVISION,
        self::ENTREVISTA,
        self::CONTRATADO,
        self::RECHAZADO,
    ];

    public const TERMINALES = [
        self::CONTRATADO,
        self::RECHAZADO,
    ];

    private const CODIGOS = [
        self::RECIBIDO => 'recibido',
        self::EN_REVISION => 'en_revision',
        self::ENTREVISTA => 'entrevista',
        self::CONTRATADO => 'contratado',
        self::RECHAZADO => 'rechazado',
    ];

    protected $table = 'estados_postulacion';

    protected $fillable = [
        'nombre',
    ];

    public function codigo(): string
    {
        return self::codigoDe($this->nombre);
    }

    public static function codigoDe(string $nombre): string
    {
        return self::CODIGOS[$nombre];
    }

    // Los IDs dependen del orden en que se sembró el catálogo, por eso se buscan por nombre
    public static function idDe(string $nombre): int
    {
        return static::where('nombre', $nombre)->firstOrFail()->id;
    }
}
