<?php

/**
 * Copa Vegas (tipo de inscripción 20).
 * Curso: 2001
 */
return [
    'tipo_id' => 20,
    'curso_id' => '2001',
    'nombre' => 'Copa Vegas',
    'sedes' => ['MEDELLÍN', 'RETIRO'],
    'interno_externo' => ['Interno', 'Externo'],
    'disciplinas' => [
        'Ajedrez' => [
            'precio' => 40000,
            'categorias' => ['Sub 6', 'Sub 8', 'Sub 10', 'Sub 12'],
        ],
        'Baloncesto' => [
            'precio' => 150000,
            'categorias' => [
                'Mini femenino',
                'Mini masculino',
                'Infantil femenino',
                'Infantil masculino',
                'Junior masculino',
            ],
        ],
        'Fútbol' => [
            'precio' => 150000,
            'categorias' => [
                'Test de habilidades',
                'Prenivel',
                'Nivel 1',
                'Nivel 2',
                'Nivel 3',
                'AC1',
            ],
        ],
        'Gimnasia' => [
            'precio' => 80000,
            'categorias' => ['Sub 6', 'Sub 8', 'Sub 10', 'Sub 12', 'Sub 14', 'Sub 16'],
        ],
        'Patinaje' => [
            'precio' => 80000,
            'categorias' => ['Escuela', 'Novatos', 'Promocional', 'Nivel A'],
        ],
        'Porrismo' => [
            'precio' => 30000,
            'categorias' => ['Mini', 'Youth', 'Senior'],
        ],
        'Taekwondo' => [
            'precio' => 80000,
            'categorias' => [
                'Baby (4-5 años)',
                'Prebenjamin (6 y 7 años)',
                'Benjamin (8-9 años)',
                'Precadete (10 y 11 años)',
                'Cadete (12 a 14 años)',
                'Juvenil (15 a 17 años)',
            ],
        ],
        'Voleibol' => [
            'precio' => 150000,
            'categorias' => ['Benjamin', 'Mini', 'Infantil', 'Premenores', 'Menores'],
        ],
    ],
];
