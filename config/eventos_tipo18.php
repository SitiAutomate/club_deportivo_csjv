<?php

/**
 * Cursos tipo 18:
 * - Festivegas: solo formulario de equipos
 * - Formulario principal: eventos individuales/equipos listados en principal_curso_ids
 */
return [
    'festivegas_curso_ids' => ['1801'],
    'principal_curso_ids' => ['1802', '1803', '1804', '1805', '1806', '1807', '1808'],
    'open_kewmgang_curso_id' => '1802',
    'med_cheer_curso_id' => '1803',
    'tkd_nacional_curso_id' => '1804',
    'copa_flores_curso_id' => '1805',
    'volleyball_fest_curso_id' => '1806',
    'baby_voleibol_curso_id' => '1807',
    'big_show_curso_id' => '1808',

    'med_cheer' => [
        'nombre' => 'Med Cheer Championships',
        'categorias' => [
            'Tiny Diamonds',
            'Mini Diamonds',
            'Youth Diamonds',
            'Senior',
        ],
        'valor' => 120000,
    ],

    'tkd_nacional' => [
        'modalidades' => [
            'Festival infantil' => 120000,
            'Combate individual' => 150000,
            'Poomsae' => 150000,
            'Combate y poomsae' => 230000,
        ],
        'categorias' => [
            'Infantil (menores de 12 años)',
            'Precadete A (2015-2016)',
            'Precadete B (2017-2018)',
            'Cadetes (2012-2014)',
            'Junior (2009 a 2011)',
        ],
        'grados' => [
            'Blanco',
            'Blanco franja amarilla',
            'Amarillo',
            'Amarillo franja verde',
            'Verde',
            'Verde franja azul',
            'Azul',
            'Azul franja roja',
            'Rojo',
            'Rojo franja negra',
        ],
        'generos' => ['Femenino', 'Masculino'],
    ],

    'copa_flores' => [
        'nombre' => 'Copa Ciudad de Flores Gimnasia',
        'categorias' => [
            'Pre nivel',
            'Test de habilidades',
            'Nivel 1',
            'Nivel 2',
            'Nivel 3',
        ],
        'valor' => 240000,
    ],

    'volleyball_fest' => [
        'nombre' => 'Volleyball Fest',
        'categorias' => [
            'Sub 14 (Premenores e Infantil)',
        ],
        'valor' => 1500000,
        'valor_label' => 'por equipo',
    ],

    'baby_voleibol' => [
        'nombre' => 'Baby Voleibol',
        'categorias' => [
            'Mini',
            'Infantil',
        ],
        'valor' => 400000,
        'valor_label' => 'por equipo',
    ],

    'big_show' => [
        'nombre' => 'The Big Show',
        'categorias' => [
            'Tiny gold',
            'Mini gold',
            'Youth gold',
            'Junior',
            'Youth emerald retiro',
        ],
        'valor' => 90000,
    ],
];
