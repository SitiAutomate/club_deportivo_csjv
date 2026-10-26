<?php

/**
 * Cursos tipo 18:
 * - Festivegas: solo formulario de equipos
 * - Formulario principal: eventos individuales/equipos listados en principal_curso_ids
 */
return [
    'festivegas_curso_ids' => ['1801'],
    'principal_curso_ids' => ['1802', '1803', '1804', '1805', '1806', '1807', '1808', '1809', '1810', '1811', '1812', '1813', '1814', '1815', '1816', '1818', '1819', '1820'],
    'open_kewmgang_curso_id' => '1802',
    'med_cheer_curso_id' => '1803',
    'tkd_nacional_curso_id' => '1804',
    'copa_flores_curso_id' => '1805',
    'volleyball_fest_curso_id' => '1806',
    'baby_voleibol_curso_id' => '1807',
    'big_show_curso_id' => '1808',
    'higland_curso_id' => '1809',
    'mini_baloncesto_curso_id' => '1810',
    'oktoberfest_curso_id' => '1811',
    'continental_stars_curso_id' => '1812',
    'tkd_fraternidad_curso_id' => '1813',
    'volleyball_columbus_curso_id' => '1814',
    'suramericano_usag_curso_id' => '1815',
    'copa_wolves_curso_id' => '1816',
    'regional_cup_curso_id' => '1818',
    'tkd_examen_cinturon_curso_id' => '1819',
    'reto_3x3_curso_id' => '1820',

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

    'higland' => [
        'nombre' => 'HIGLAND',
        'categorias' => [
            'Tiny Diamonds',
            'Mini Diamonds',
            'Youth Diamonds',
            'Youth Gold',
            'Junior',
            'Senior',
        ],
        'valor' => 140000,
    ],

    'mini_baloncesto' => [
        'nombre' => 'II Festival Premini',
        'categorias' => [
            'II FESTIVAL PREMINI (NACIDOS 2018, 2019, 2020)',
        ],
        'valor' => 110000,
        'valor_label' => 'por equipo',
    ],

    'oktoberfest' => [
        'nombre' => 'Oktoberfest 2026 – Gimnasia Artística',
        'categorias' => [
            'Prenivel',
            'Test de Habilidades',
            'Nivel 1',
            'Nivel 2',
            'Nivel 3',
            'Age Group',
        ],
        'valor' => 280000,
    ],

    'continental_stars' => [
        'nombre' => 'Continental Stars',
        'categorias' => [
            'Youth',
            'Diamonds',
            'Senior',
        ],
        'valor' => 220000,
    ],

    'tkd_fraternidad' => [
        'nombre' => 'Open de taekwondo por la fraternidad',
        'valor' => 100000,
        'cinturones' => [
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
    ],

    'volleyball_columbus' => [
        'nombre' => 'Volleyball Competition – The Columbus School',
        'categorias' => [
            'Infantil',
        ],
        'valor' => 500000,
        'valor_label' => 'por equipo',
    ],

    'suramericano_usag' => [
        'nombre' => 'Campeonato Suramericano de Niveles USAG',
        'categorias' => [
            'Prenivel',
            'Nivel 1',
            'Nivel 2',
            'Nivel 3',
            'Nivel 4',
            'Nivel 5',
            'Nivel 6',
            'Nivel 7',
            'Nivel 8',
        ],
        'valores' => [
            'Prenivel' => 241000,
            'Nivel 1' => 341000,
            'Nivel 2' => 341000,
            'Nivel 3' => 341000,
            'Nivel 4' => 341000,
            'Nivel 5' => 341000,
            'Nivel 6' => 341000,
            'Nivel 7' => 341000,
            'Nivel 8' => 341000,
        ],
        'valor' => 341000,
    ],

    'copa_wolves' => [
        'nombre' => 'Copa Internacional Wolves 2026',
        'categorias' => [],
        'valor' => 1300000,
        'valor_label' => 'por equipo',
    ],

    'regional_cup' => [
        'nombre' => 'Regional Cup 2026',
        'categorias' => [
            'Tiny Gold',
            'Youth Gold',
            'Youth Emerald',
            'Youth Diamonds',
            'Junior',
            'Senior',
        ],
        'valor' => 130000,
        'valor_label' => 'por deportista',
    ],

    'tkd_examen_cinturon' => [
        'nombre' => 'Examen y ceremonia para ascenso de cinturón 2026',
        'valor' => 90000,
        'cinturones_actual' => [
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
        'cinturones_aspira' => [
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
    ],

    'reto_3x3' => [
        'nombre' => 'Reto 3x3 Tour Colombia',
        'categorias' => [
            'U15',
            'U17',
            'Amateur masculino',
            'Única femenina',
            'Élite masculino',
        ],
        'valores' => [
            'U15' => 350000,
            'U17' => 350000,
            'Amateur masculino' => 450000,
            'Única femenina' => 400000,
            'Élite masculino' => 500000,
        ],
        'valor' => 350000,
        'valor_label' => 'por equipo',
    ],
];
