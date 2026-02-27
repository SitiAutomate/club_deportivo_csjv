<?php

/**
 * Configuración de tipos de inscripción.
 * Tipo 1 = Cursos (con filtros mes, sede, línea, actividad).
 * Otros tipos = Selector simple o actividad directa (sin selector).
 *
 * layout: 'cursos' = tipo 1 (filtros completos)
 *         'selector' = selector de cursos por tipo (campamentos, salidas, etc.)
 *         'directo' = actividad con un solo curso, no requiere selección
 *
 * hasSelector: true = mostrar selector para elegir curso/campamento/salida
 *              false = actividad directa, usa el único curso (de get-cursos-por-tipo)
 *
 * filterByDate, muestraDatosAdicionales, tieneTemplate
 */
return [
    1 => [
        'layout' => 'cursos',
        'hasSelector' => true,
        'filterByDate' => false,
        'defaultSede' => null,
        'defaultMes' => null,
        'hasTransporte' => true,
        'selectorName' => 'curso_ids',
        'muestraDatosAdicionales' => false,
        'tieneTemplate' => false,
        'usaApiInscripcion' => true,
    ],
    2 => [
        'usaApiInscripcion' => false,
        'layout' => 'selector',
        'hasSelector' => true,
        'filterByDate' => true,
        'defaultSede' => 'MEDELLÍN',
        'defaultMes' => null,
        'hasTransporte' => false,
        'selectorName' => 'campamento_id',
        'labelSelector' => 'Seleccione campamento',
        'muestraDatosAdicionales' => true,
        'tieneTemplate' => true,
    ],
    5 => [
        'usaApiInscripcion' => false,
        'layout' => 'selector',
        'hasSelector' => true,
        'filterByDate' => true,
        'defaultSede' => 'MEDELLÍN',
        'defaultMes' => null,
        'hasTransporte' => false,
        'selectorName' => 'salida_id',
        'labelSelector' => 'Seleccione la salida',
        'muestraDatosAdicionales' => true,
        'tieneTemplate' => true,
    ],
    14 => [
        'usaApiInscripcion' => false,
        'layout' => 'directo',
        'hasSelector' => false,
        'filterByDate' => true,
        'defaultSede' => 'MEDELLÍN',
        'defaultMes' => null,
        'hasTransporte' => false,
        'selectorName' => 'curso_id',
        'muestraDatosAdicionales' => true,
        'tieneTemplate' => true,
    ],
];
