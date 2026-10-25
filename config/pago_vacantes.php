<?php

/*
|--------------------------------------------------------------------------
| Pago simulado de vacantes (HU-03)
|--------------------------------------------------------------------------
|
| Tarifa y unidad son la referencia de la HU (aprox. S/ 100 por puesto).
| Se usan salvo que el dueño del negocio indique otros valores.
|
*/

return [

    // Monto en soles por cada unidad de cobro
    'tarifa_por_puesto' => 100,

    // 'puesto'  → monto = tarifa × max_contrataciones de la vacante
    // 'vacante' → monto = tarifa (un solo cobro por anuncio)
    'unidad_cobro' => 'puesto',

    'moneda' => 'PEN',

];
