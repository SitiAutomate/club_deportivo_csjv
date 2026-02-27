<?php

/**
 * Configuración de participantes adicionales por curso.
 * Clave: "tipoId_idCurso" (ej: "2_2266")
 *
 * Campos disponibles (columnas en participantes_adicionales):
 *   documento, nombre, fechanacimiento, celular, email
 *
 * Por defecto se usan todos. Puede limitar con 'fields'.
 */
return [
    '2_2266' => [
        'max' => 2,
        'label' => 'Participante adicional',
        'fields' => ['documento', 'nombre', 'celular', 'email'],
        'labels' => [
            'documento' => 'Documento',
            'nombre' => 'Nombre completo',
            'celular' => 'Celular',
            'email' => 'Correo electrónico',
        ],
    ],
    // Ejemplo para otro curso:
    // '2_2300' => ['max' => 1, 'fields' => ['documento', 'nombre']],
];
