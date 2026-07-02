<?php

/**
 * Configuración Level Up (tipo de inscripción 4).
 * Cursos por sede y nivel.
 */
return [
    'tipo_id' => 4,
    'sedes' => ['MEDELLÍN', 'RETIRO'],
    'cursos_por_sede_nivel' => [
        'MEDELLÍN' => [
            1 => ['id' => '2351', 'nombre' => 'LEVEL UP LEARNING M', 'nivel' => 1],
            2 => ['id' => '2353', 'nombre' => 'LEVEL UP LEARNING 2 M', 'nivel' => 2],
        ],
        'RETIRO' => [
            1 => ['id' => '2352', 'nombre' => 'LEVEL UP LEARNING R', 'nivel' => 1],
            2 => ['id' => '2354', 'nombre' => 'LEVEL UP LEARNING 2 R', 'nivel' => 2],
        ],
    ],
    'modalidades_nivel_1' => ['Individual', 'Grupal'],
    'sesion_nivel_2' => 'Individual',
    'niveles' => [
        1 => [
            'label' => 'Nivel 1 – Fortalecimiento de habilidades',
            'nombre' => 'Fortalecimiento de habilidades',
        ],
        2 => [
            'label' => 'Nivel 2 – Ruta de acompañamiento especializada',
            'nombre' => 'Ruta de acompañamiento especializada',
        ],
    ],
    'grado_academico_column' => 'categoria',
    'nivel_2_diagnostico_column' => 'Asignatura',
];
