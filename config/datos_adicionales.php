<?php

/**
 * Configuración de campos en "4. Datos adicionales"
 * Cada campo se puede activar/desactivar y asignar a una columna de inscripciones_1
 *
 * Campos:
 *   - key: identificador único (name del input)
 *   - label: etiqueta visible
 *   - type: checkbox | text | textarea | select | select_si_no_text
 *   - column: columna en inscripciones_1 (parqueadero, IDAsign, Modalidad, OBSERVACION, etc.)
 *   - required: true|false
 *   - enabled: true|false
 *
 * Para select_si_no_text: incluye textFieldName, textPlaceholder
 * Para select: incluye options como array [valor => etiqueta]
 */
return [
    'autorizo_imagen' => [
        'label' => 'Autorizo el uso de imagen (fotografías y vídeos de la actividad)',
        'type' => 'select_si_no',
        'column' => 'parqueadero',
        'required' => false,
        'enabled' => true,
    ],
    'toma_medicamento' => [
        'label' => '¿El participante actualmente toma algún medicamento?',
        'type' => 'select_si_no_text',
        'column' => 'IDAsign',
        'required' => false,
        'enabled' => true,
        'textFieldName' => 'medicamento_texto',
        'textPlaceholder' => 'Nombre del medicamento y dosis...',
    ],
    'restriccion_alimentaria' => [
        'label' => '¿Tiene restricción o condición alimentaria?',
        'type' => 'select_si_no_text',
        'column' => 'Modalidad',
        'required' => false,
        'enabled' => true,
        'textFieldName' => 'restriccion_texto',
        'textPlaceholder' => 'Ej: intolerancia a lactosa, alergia...',
    ],
    'observacion' => [
        'label' => 'Observaciones',
        'type' => 'textarea',
        'column' => 'OBSERVACION',
        'required' => false,
        'enabled' => true,
        'placeholder' => 'Cualquier información adicional que debamos conocer...',
        'rows' => 3,
    ],
    'fecha_interes_english_camp' => [
        'label' => '¿En cuál fecha del English Camp está interesado?',
        'type' => 'select',
        'column' => 'categoria',
        'required' => true,
        'enabled' => true,
        'show_only_for' => [
            'tipo_ids' => [2],
            'curso_ids' => ['2262'],
        ],
        'options' => [
            '25 al 27 de noviembre de 2026' => '25 al 27 de noviembre de 2026',
            '3 al 5 de diciembre de 2026' => '3 al 5 de diciembre de 2026',
        ],
    ],
    'modalidad_pago_english_camp' => [
        'label' => 'Modalidad de pago (English Camp)',
        'type' => 'select',
        'column' => 'Sesión',
        'required' => true,
        'enabled' => true,
        'show_only_for' => [
            'tipo_ids' => [2],
            'curso_ids' => ['2262'],
        ],
        'options' => [
            'Pronto pago ($1.650.000 — hasta 31 de octubre de 2026)' => 'Pronto pago ($1.650.000 — hasta 31 de octubre de 2026)',
            'Precio estándar ($1.720.000)' => 'Precio estándar ($1.720.000)',
        ],
    ],
];