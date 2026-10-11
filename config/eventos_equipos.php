<?php

/**
 * Catálogo de eventos del formulario de inscripción por equipos.
 * Cada evento define límites, campos y planilla de deportistas.
 */
return [
    'eventos' => [
        '2001' => [
            'id' => '2001',
            'modo' => 'copa_vegas',
            'tipo_id' => 20,
            'nombre' => 'Copa Vegas',
            'activo' => true,
            'max_equipos_por_envio' => 10,
            'max_equipos_total' => 10,
            'campos' => [
                'nombre_equipo' => true,
                'club' => false,
                'disciplina' => true,
                'rama' => true,
                'categoria' => true,
                'entrenador_nombre' => true,
                'entrenador_documento' => true,
                'entrenador_contacto' => true,
            ],
            'disciplina_fija' => null,
            'categorias' => null, // por disciplina (Copa Vegas)
            'ramas' => ['Femenina', 'Masculina', 'Mixta'],
            'deportistas' => [
                'campos' => ['nombre_completo', 'documento', 'fecha_nacimiento'],
                'requiere_fecha_nacimiento' => true,
                'min' => 1,
                'max' => 50,
            ],
            'nota_responsable' => 'Ingrese el documento del responsable de pago y valídelo. Si no está registrado en el sistema, podrá completar sus datos a continuación.',
            'label_responsable' => 'Responsable de pago',
            'info_corta' => 'Inscripción de equipos de fútbol, baloncesto y voleibol.',
            'template_tipo' => 20,
        ],

        '1817' => [
            'id' => '1817',
            'modo' => 'festival_futbol',
            'tipo_id' => 18,
            'nombre' => 'Festival de Fútbol para todas las categorías',
            'activo' => true,
            'max_equipos_por_envio' => 1,
            'max_equipos_total' => 20,
            'campos' => [
                'nombre_equipo' => false,
                'club' => true,
                'disciplina' => false,
                'rama' => false,
                'categoria' => true,
                'entrenador_nombre' => true,
                'entrenador_documento' => false,
                'entrenador_contacto' => false,
            ],
            'disciplina_fija' => 'Fútbol',
            'categorias' => ['S6', 'S7', 'S8', 'S9', 'S10', 'S11', 'S12', 'S13', 'S14', 'S15', 'S16'],
            'ramas' => [],
            'deportistas' => [
                'campos' => ['nombre_completo', 'documento'],
                'requiere_fecha_nacimiento' => false,
                'min' => 1,
                'max' => 20,
            ],
            'nota_responsable' => 'En los campos de responsable, ingrese los datos del entrenador encargado del equipo.',
            'label_responsable' => 'Entrenador / Responsable',
            'info_corta' => '18 de octubre · 8:30 a. m. · Sede El Retiro. Un equipo por envío.',
            'detalle' => [
                'fecha' => '18 de octubre de 2026',
                'hora' => '8:30 a. m.',
                'lugar' => 'Club Deportivo San José de Las Vegas – Sede El Retiro',
            ],
            'template_tipo' => 18,
        ],
    ],
];
