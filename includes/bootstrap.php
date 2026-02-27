<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno desde .env (raíz del proyecto)
require_once __DIR__ . '/env.php';
loadEnv(__DIR__ . '/..');

// Cargar funciones
require_once __DIR__ . '/functions.php';

// Cargar configuración de BD (usa Medoo)
require_once __DIR__ . '/../config/database.php';

// Cargar modelos
require_once __DIR__ . '/../models/Participante.php';
require_once __DIR__ . '/../models/Responsable.php';
require_once __DIR__ . '/../models/InscripcionTipo.php';
require_once __DIR__ . '/../models/Inscripcion.php';
require_once __DIR__ . '/../models/Curso.php';
require_once __DIR__ . '/../models/Actividad.php';
require_once __DIR__ . '/../models/Linea.php';
require_once __DIR__ . '/../models/Asignatura.php';
require_once __DIR__ . '/../models/Ciudad.php';
require_once __DIR__ . '/../models/Mes.php';
require_once __DIR__ . '/../models/ParticipanteAdicional.php';
require_once __DIR__ . '/ExternalApiService.php';
