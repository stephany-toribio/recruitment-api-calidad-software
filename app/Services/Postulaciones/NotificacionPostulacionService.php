<?php

namespace App\Services\Postulaciones;

use App\Mail\ContratacionMail;
use App\Mail\RechazoMail;
use App\Models\EstadoPostulacion;
use App\Models\NotificacionPostulacion;
use App\Models\Postulacion;
use Illuminate\Support\Facades\Mail;
use Throwable;


class NotificacionPostulacionService
{
    // Se llama después de confirmar la transacción, para que un fallo del correo no revierta el cambio de estado
    public function notificarDecision(Postulacion $postulacion): void
    {
        //load, el estado tiene que ser el que dejo el kanban
        $postulacion->load(['estado', 'candidato.usuario', 'empleo.empresa']);

        $tipo = match ($postulacion->estado->nombre)
        {
            EstadoPostulacion::CONTRATADO => NotificacionPostulacion::CONTRATADO,
            EstadoPostulacion::RECHAZADO  => NotificacionPostulacion::RECHAZADO,
            default                       => null,
        };

        //solo se notifican las decisiones finales
        if($tipo === null){
            return;
        }

        $candidato = $postulacion ->candidato;
        $correo = $candidato -> usuario ->correo;
        $datos = [$candidato->nombre, $postulacion->empleo->titulo, $postulacion->empleo->empresa->razon_social];

        $mail = $tipo ===NotificacionPostulacion::CONTRATADO
            ? new ContratacionMail (...$datos)
            : new RechazoMail (...$datos);
        
        try{
            Mail::to($correo)->send($mail);
            $resultado=NotificacionPostulacion::ENVIADO;
            $error=null;
        }catch(Throwable $e){
            //no se relanza : el cambio de estado ya quedo guardado. Sin reintentohast que se defina como sera

            report($e);
            $resultado=NotificacionPostulacion::FALLIDO;
            //el check de la tabla exige error no nulo cuando es fallido
            $error=$e->getMessage() !== ''? $e->getMessage(): $e::class;

        }

        NotificacionPostulacion::create([
            'postulacion_id' => $postulacion->id,
            'tipo'           => $tipo,
            'destinatario'   => $correo, // el correo del candidato al momento del envío
            'resultado'      => $resultado,
            'error'          => $error,
        ]);
    }
}
