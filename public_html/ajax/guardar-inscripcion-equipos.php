<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/EmailService.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/copa_vegas.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
}
csrfValidate();

@set_time_limit(60);
@ignore_user_abort(true);

header('Content-Type: application/json; charset=utf-8');
$traceId = bin2hex(random_bytes(8));
header('X-Trace-Id: ' . $traceId);
$tInicio = microtime(true);

$input = getPostData();
$documentoResponsable = trim($input['responsable_documento'] ?? $input['responsable_id'] ?? '');
$idCurso = trim((string) ($input['curso_id'] ?? $input['IDCurso'] ?? ''));
$equipos = $input['equipos'] ?? [];
$politicas = $input['politicas'] ?? $input['Politicas'] ?? null;

if ($documentoResponsable === '' || $idCurso === '') {
    jsonResponse(['success' => false, 'error' => 'Faltan datos del responsable o evento.', 'traceId' => $traceId], 400);
}
if ($politicas !== 'Si') {
    jsonResponse(['success' => false, 'error' => 'Debe aceptar la autorización para el tratamiento de datos personales.', 'traceId' => $traceId], 400);
}
if (!is_array($equipos) || count($equipos) === 0) {
    jsonResponse(['success' => false, 'error' => 'Debe registrar al menos un equipo.', 'traceId' => $traceId], 400);
}

$responsableModel = new Responsable($database);
$responsable = $responsableModel->getByDocumento($documentoResponsable);
if (!$responsable) {
    jsonResponse(['success' => false, 'error' => 'No encontramos un responsable con ese documento.', 'traceId' => $traceId], 404);
}

$curso = $database->get('cursos_2025', [
    'ID_Curso', 'Nombre_del_curso', 'Nombre_Corto_Curso', 'Tipo', 'Tarifa_Curso', 'Estado_del_curso'
], ['ID_Curso' => $idCurso]);
if (!$curso) {
    jsonResponse(['success' => false, 'error' => 'El evento seleccionado no existe.', 'traceId' => $traceId], 404);
}
if (!copaVegasEsCursoValido($idCurso)) {
    jsonResponse(['success' => false, 'error' => 'Este formulario solo permite inscripción de equipos para Copa Vegas.', 'traceId' => $traceId], 400);
}
$tipoId = (int) ($curso['Tipo'] ?? copaVegasTipoId());
$nombreCurso = $curso['Nombre_del_curso'] ?? $curso['Nombre_Corto_Curso'] ?? $idCurso;

$celularValido = static function (string $tel): bool {
    return (bool) preg_match('/^3\d{9}$/', $tel);
};

$equiposNormalizados = [];
foreach ($equipos as $i => $eq) {
    $idx = $i + 1;
    $nombreEquipo = trim((string) ($eq['nombre_equipo'] ?? ''));
    $disciplina = trim((string) ($eq['disciplina'] ?? ''));
    $rama = trim((string) ($eq['rama'] ?? ''));
    $categoria = trim((string) ($eq['categoria'] ?? ''));
    $entrenadorNombre = trim((string) ($eq['entrenador_nombre'] ?? ''));
    $entrenadorDocumento = trim((string) ($eq['entrenador_documento'] ?? ''));
    $entrenadorContacto = trim((string) ($eq['entrenador_contacto'] ?? ''));
    $deportistas = $eq['deportistas'] ?? [];

    if ($nombreEquipo === '' || $disciplina === '' || $rama === '' || $categoria === '') {
        jsonResponse(['success' => false, 'error' => "Equipo {$idx}: complete nombre, disciplina, rama y categoría.", 'traceId' => $traceId], 400);
    }
    if (!copaVegasEsDisciplinaEquipo($disciplina)) {
        jsonResponse(['success' => false, 'error' => "Equipo {$idx}: disciplina no válida para inscripción por equipos.", 'traceId' => $traceId], 400);
    }
    if (!copaVegasCategoriaValida($disciplina, $categoria)) {
        jsonResponse(['success' => false, 'error' => "Equipo {$idx}: categoría no válida para {$disciplina}.", 'traceId' => $traceId], 400);
    }
    if ($entrenadorNombre === '' || $entrenadorDocumento === '' || $entrenadorContacto === '') {
        jsonResponse(['success' => false, 'error' => "Equipo {$idx}: complete nombre, documento y celular del entrenador.", 'traceId' => $traceId], 400);
    }
    if (!$celularValido($entrenadorContacto)) {
        jsonResponse(['success' => false, 'error' => "Equipo {$idx}: el celular del entrenador debe tener 10 dígitos y empezar por 3.", 'traceId' => $traceId], 400);
    }
    if (!in_array($rama, ['Femenina', 'Masculina', 'Mixta'], true)) {
        jsonResponse(['success' => false, 'error' => "Equipo {$idx}: rama inválida.", 'traceId' => $traceId], 400);
    }

    if (!is_array($deportistas)) $deportistas = [];
    $deportistasActivos = [];
    foreach ($deportistas as $j => $d) {
        $nombreDep = trim((string) ($d['nombre_completo'] ?? $d['nombre'] ?? ''));
        $fechaDep = trim((string) ($d['fecha_nacimiento'] ?? ''));
        $docDep = trim((string) ($d['documento'] ?? ''));
        $activa = $nombreDep !== '' || $fechaDep !== '' || $docDep !== '';
        if (!$activa) {
            continue;
        }
        $numDep = $j + 1;
        if ($nombreDep === '') {
            jsonResponse(['success' => false, 'error' => "Equipo {$idx}, deportista #{$numDep}: ingrese el nombre completo.", 'traceId' => $traceId], 400);
        }
        if ($fechaDep === '') {
            jsonResponse(['success' => false, 'error' => "Equipo {$idx}, deportista #{$numDep}: ingrese la fecha de nacimiento.", 'traceId' => $traceId], 400);
        }
        if ($docDep === '') {
            jsonResponse(['success' => false, 'error' => "Equipo {$idx}, deportista #{$numDep}: ingrese el número de documento.", 'traceId' => $traceId], 400);
        }
        $deportistasActivos[] = [
            'nombre_completo' => $nombreDep,
            'fecha_nacimiento' => $fechaDep,
            'documento' => $docDep,
        ];
    }
    if (count($deportistasActivos) < 1) {
        jsonResponse(['success' => false, 'error' => "Equipo {$idx}: registre al menos un deportista con todos sus datos.", 'traceId' => $traceId], 400);
    }

    $equiposNormalizados[] = [
        'nombre_equipo' => $nombreEquipo,
        'disciplina' => $disciplina,
        'rama' => $rama,
        'categoria' => $categoria,
        'entrenador_nombre' => $entrenadorNombre,
        'entrenador_documento' => $entrenadorDocumento,
        'entrenador_contacto' => $entrenadorContacto,
        'asistente_nombre' => null,
        'asistente_documento' => null,
        'asistente_contacto' => null,
        'deportistas' => $deportistasActivos,
    ];
}

$anio = (int) date('Y');
$mes = str_pad((string) date('n'), 2, '0', STR_PAD_LEFT);

$inscripcionModel = new Inscripcion($database);
$equipoModel = new Equipo($database);
$deportistaModel = new EquipoDeportista($database);

$inscripcionExistente = $inscripcionModel->getPorResponsableYCurso($documentoResponsable, $idCurso, $anio, $tipoId);
$equiposExistentes = [];
$idInscripcionExistente = 0;
if ($inscripcionExistente) {
    $idInscripcionExistente = (int) ($inscripcionExistente['IDInscripcion'] ?? 0);
    if ($idInscripcionExistente > 0) {
        $equiposExistentes = $equipoModel->getByInscripcion($idInscripcionExistente);
    }
}

$maxEquiposPorInscripcion = 10;
$totalEquiposExistentes = count($equiposExistentes);
$cupoDisponible = $maxEquiposPorInscripcion - $totalEquiposExistentes;
if ($cupoDisponible <= 0) {
    jsonResponse([
        'success' => false,
        'error' => "Este responsable ya alcanzó el máximo de {$maxEquiposPorInscripcion} equipos para este evento.",
        'traceId' => $traceId
    ], 409);
}
if (count($equipos) > $cupoDisponible) {
    jsonResponse([
        'success' => false,
        'error' => "Solo puede registrar {$cupoDisponible} equipo(s) adicional(es) para este evento (ya tiene {$totalEquiposExistentes} registrado(s)).",
        'traceId' => $traceId
    ], 400);
}

$equiposExistentesDetalle = [];
$docsExistentes = [];
foreach ($equiposExistentes as $eq) {
    $idEqExist = (int) ($eq['IDEquipo'] ?? 0);
    $deportistasExist = $idEqExist > 0 ? $deportistaModel->getByEquipo($idEqExist) : [];
    foreach ($deportistasExist as $d) {
        $doc = trim((string) ($d['documento'] ?? ''));
        if ($doc !== '') $docsExistentes[$doc] = true;
    }
    $equiposExistentesDetalle[] = array_merge($eq, ['deportistas' => $deportistasExist]);
}

$docsVistos = $docsExistentes;
foreach ($equiposNormalizados as $i => $eq) {
    $idxEq = $i + 1;
    foreach ($eq['deportistas'] as $j => $d) {
        $idxD = $j + 1;
        $doc = trim((string) ($d['documento'] ?? ''));
        if (isset($docsVistos[$doc])) {
            $msg = isset($docsExistentes[$doc])
                ? "Equipo {$idxEq}, deportista #{$idxD}: el documento {$doc} ya está registrado en un equipo previamente inscrito."
                : "Equipo {$idxEq}, deportista #{$idxD}: el documento {$doc} está repetido en esta inscripción.";
            jsonResponse(['success' => false, 'error' => $msg, 'traceId' => $traceId], 400);
        }
        $docsVistos[$doc] = true;
    }
}

$periodo = $mes . str_pad((string) ($anio % 100), 2, '0', STR_PAD_LEFT);
$equiposNuevos = count($equiposNormalizados);
$totalFinal = $totalEquiposExistentes + $equiposNuevos;

$labelEquipo = static function (array $e): string {
    $disc = $e['disciplina'] ?? '';
    return ($e['nombre_equipo'] ?? '') . ' (' . ($disc !== '' ? $disc . ' / ' : '') . ($e['rama'] ?? '') . ' / ' . ($e['categoria'] ?? '') . ')';
};

try {
    $idInscripcion = $idInscripcionExistente;
    if ($idInscripcion <= 0) {
        $idInscripcion = $inscripcionModel->create(
            $documentoResponsable,
            $documentoResponsable,
            $tipoId,
            [
                'IDCurso' => $idCurso,
                'nombreCurso' => $nombreCurso,
                'año' => $anio,
                'Mes' => $mes,
                'Periodo' => $periodo,
                'Fecha_Inscripción' => date('Y-m-d'),
                'Politicas' => 'Si',
                'Estado' => 'ACTIVO',
                'Modalidad' => 'Equipos',
                'OBSERVACION' => json_encode([
                    'evento' => $nombreCurso,
                    'modo' => 'copa_vegas_equipos',
                    'total_equipos' => $equiposNuevos,
                    'equipos' => array_map($labelEquipo, $equiposNormalizados),
                ], JSON_UNESCAPED_UNICODE),
            ]
        );

        if ($idInscripcion <= 0) {
            AppLogger::error('guardar-inscripcion-equipos: insert id <= 0', [
                'traceId' => $traceId,
                'responsable' => $documentoResponsable,
                'curso' => $idCurso
            ]);
            jsonResponse(['success' => false, 'error' => 'No fue posible registrar la inscripción.', 'traceId' => $traceId], 500);
        }
    } else {
        $nombresExistentes = array_map($labelEquipo, $equiposExistentes);
        $nombresNuevos = array_map($labelEquipo, $equiposNormalizados);
        $database->update('inscripciones_1', [
            'OBSERVACION' => json_encode([
                'evento' => $nombreCurso,
                'modo' => 'copa_vegas_equipos',
                'total_equipos' => $totalFinal,
                'equipos' => array_merge($nombresExistentes, $nombresNuevos),
            ], JSON_UNESCAPED_UNICODE)
        ], ['IDInscripcion' => $idInscripcion]);
    }

    $equiposCreados = [];
    foreach ($equiposNormalizados as $eq) {
        $idEquipo = $equipoModel->create($idInscripcion, $idCurso, $eq);
        if ($idEquipo > 0) {
            $deportistaModel->crearLote($idEquipo, $eq['deportistas']);
            $eq['IDEquipo'] = $idEquipo;
        }
        $equiposCreados[] = $eq;
    }

    $tGuardado = microtime(true);
    AppLogger::info('guardar-inscripcion-equipos: equipos guardados', [
        'traceId' => $traceId,
        'idInscripcion' => $idInscripcion,
        'responsable' => $documentoResponsable,
        'curso' => $idCurso,
        'equiposNuevos' => $equiposNuevos,
        'equiposExistentes' => $totalEquiposExistentes,
        'totalFinal' => $totalFinal,
        'duracion_ms' => (int) (($tGuardado - $tInicio) * 1000)
    ]);

    $correo = trim($responsable['Correo_Responsable'] ?? '');
    $responsableNombre = $responsable['Nombre_Completo']
        ?? trim(($responsable['Nombres'] ?? '') . ' ' . ($responsable['Apellidos'] ?? ''));

    $respuesta = [
        'success' => true,
        'inscripcion_id' => $idInscripcion,
        'evento' => $nombreCurso,
        'equipos_nuevos' => $equiposNuevos,
        'equipos_existentes' => $totalEquiposExistentes,
        'total_equipos' => $totalFinal,
        'emailEnviado' => null,
        'emailError' => null,
        'emailPendiente' => $correo !== '',
        'traceId' => $traceId,
    ];

    $payload = json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    header('Content-Length: ' . strlen($payload));
    header('Connection: close');
    echo $payload;

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        if (function_exists('ob_get_level')) {
            while (ob_get_level() > 0) { @ob_end_flush(); }
        }
        @flush();
    }

    if ($correo !== '') {
        try {
            $emailService = new EmailService();
            $emailEnviado = $emailService->enviarConfirmacionInscripcionEquipos(
                $correo,
                $responsableNombre,
                $nombreCurso,
                $equiposCreados,
                $equiposExistentesDetalle
            );
            $tEmail = microtime(true);
            if (!$emailEnviado) {
                AppLogger::warning('guardar-inscripcion-equipos: email no enviado', [
                    'traceId' => $traceId,
                    'correo' => $correo,
                    'emailError' => $emailService->getLastError(),
                    'email_ms' => (int) (($tEmail - $tGuardado) * 1000)
                ]);
            } else {
                AppLogger::info('guardar-inscripcion-equipos: email enviado', [
                    'traceId' => $traceId,
                    'correo' => $correo,
                    'email_ms' => (int) (($tEmail - $tGuardado) * 1000)
                ]);
            }
        } catch (Throwable $eMail) {
            AppLogger::error('guardar-inscripcion-equipos: excepción al enviar email', [
                'traceId' => $traceId,
                'correo' => $correo,
                'error' => $eMail->getMessage()
            ]);
        }
    } else {
        AppLogger::warning('guardar-inscripcion-equipos: responsable sin correo', [
            'traceId' => $traceId,
            'responsable' => $documentoResponsable
        ]);
    }
    exit;
} catch (Exception $e) {
    AppLogger::error('guardar-inscripcion-equipos: ' . $e->getMessage(), [
        'traceId' => $traceId,
        'trace' => $e->getTraceAsString()
    ]);
    jsonResponse(['success' => false, 'error' => 'Error al guardar: ' . $e->getMessage(), 'traceId' => $traceId], 500);
}
