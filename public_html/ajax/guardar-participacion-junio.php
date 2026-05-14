<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/EmailService.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_once __DIR__ . '/../../includes/participacion_junio.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
}
csrfValidate();

header('Content-Type: application/json; charset=utf-8');
$traceId = bin2hex(random_bytes(8));
header('X-Trace-Id: ' . $traceId);

if (!participacionJunioHabilitada()) {
    jsonResponse(['success' => false, 'error' => 'El formulario no está disponible en este momento.', 'traceId' => $traceId], 403);
}

$input = getPostData();
$documento = trim($input['documento'] ?? $input['participante_id'] ?? '');
$anio = (int) ($input['anio'] ?? date('Y'));
$mesJunio = '06';
$periodoJunio = '0626';
$tipoId = 1;

if ($documento === '') {
    jsonResponse(['success' => false, 'error' => 'El documento del participante es requerido', 'traceId' => $traceId], 400);
}

$validacion = validarDocumentoParticipante($documento);
if (!$validacion['valid']) {
    jsonResponse(['success' => false, 'error' => $validacion['error'], 'traceId' => $traceId], 400);
}
$documento = normalizarDocumentoParticipante($documento);

if (($input['politicas'] ?? $input['Politicas'] ?? '') !== 'Si') {
    jsonResponse(['success' => false, 'error' => 'Debe aceptar la autorización para el tratamiento de datos personales.', 'traceId' => $traceId], 400);
}

$cursoIds = $input['curso_ids'] ?? [];
if (!is_array($cursoIds)) {
    $cursoIds = $cursoIds ? [strval($cursoIds)] : [];
}
$cursoIds = array_values(array_unique(array_filter(array_map('strval', $cursoIds))));
if (empty($cursoIds)) {
    jsonResponse(['success' => false, 'error' => 'Seleccione al menos un curso.', 'traceId' => $traceId], 400);
}

$participanteModel = new Participante($database);
$participante = $participanteModel->getByDocumento($documento);
if (!$participante) {
    jsonResponse(['success' => false, 'error' => 'No encontramos un participante registrado con ese documento.', 'traceId' => $traceId], 404);
}

$responsableDoc = trim($participante['IDResponsable'] ?? $participante['responsable_id_real'] ?? '');
if ($responsableDoc === '') {
    jsonResponse(['success' => false, 'error' => 'El participante no tiene un responsable asociado.', 'traceId' => $traceId], 400);
}

$mesActual = str_pad((string) date('n'), 2, '0', STR_PAD_LEFT);
$inscripcionModel = new Inscripcion($database);
$inscripcionesMes = $inscripcionModel->getCursosActivosParticipante($documento, $mesActual, $anio, $tipoId);
$porCursoMes = [];
foreach ($inscripcionesMes as $row) {
    $id = (string) ($row['IDCurso'] ?? '');
    if ($id !== '' && !isset($porCursoMes[$id])) {
        $porCursoMes[$id] = $row;
    }
}

$creadas = [];
$omitidas = [];
$errores = [];

foreach ($cursoIds as $idCurso) {
    if (!isset($porCursoMes[$idCurso])) {
        $errores[] = "El curso {$idCurso} no corresponde a una inscripción activa del mes actual.";
        continue;
    }
    if ($inscripcionModel->tieneInscripcionJunioBloqueada($documento, $idCurso, $anio, $tipoId)) {
        $omitidas[] = [
            'id' => $idCurso,
            'nombre' => $porCursoMes[$idCurso]['nombreCurso'] ?? $idCurso,
            'motivo' => 'Ya existe inscripción en junio para este curso.'
        ];
        continue;
    }

    $base = $porCursoMes[$idCurso];
    $idInscripcion = $inscripcionModel->create($documento, $responsableDoc, $tipoId, [
        'IDCurso' => $idCurso,
        'Fecha_Inscripción' => date('Y-m-d'),
        'año' => $anio,
        'Mes' => $mesJunio,
        'Periodo' => $periodoJunio,
        'Sede' => $base['Sede'] ?? null,
        'nombreCurso' => $base['nombreCurso'] ?? null,
        'Politicas' => 'Si',
        'Estado' => 'INTERESADO'
    ]);

    if ($idInscripcion <= 0) {
        $errores[] = "No fue posible confirmar la inscripción en junio para el curso {$idCurso}.";
        continue;
    }

    if (class_exists('AppLogger')) {
        AppLogger::info('participacion_junio creada', [
            'traceId' => $traceId,
            'idInscripcion' => $idInscripcion,
            'participante' => $documento,
            'curso' => $idCurso
        ]);
    }

    $creadas[] = [
        'id' => $idCurso,
        'nombre' => $base['nombreCurso'] ?? $idCurso,
        'idInscripcion' => $idInscripcion
    ];
}

if (empty($creadas)) {
    $mensaje = !empty($errores)
        ? implode(' ', $errores)
        : 'No se confirmó la inscripción en junio para ningún curso. Revise si ya estaba inscrito en junio.';
    jsonResponse([
        'success' => false,
        'error' => $mensaje,
        'omitidas' => $omitidas,
        'traceId' => $traceId
    ], 400);
}

$participanteNombre = $participante['Nombre_Completo']
    ?? trim(($participante['Primer_Nombre'] ?? '') . ' ' . ($participante['Primer_Apellido'] ?? ''));

$emailEnviado = false;
$emailError = null;
$responsableModel = new Responsable($database);
$responsable = $responsableModel->getByDocumento($responsableDoc);
$correo = trim($responsable['Correo_Responsable'] ?? '');

if ($correo !== '') {
    $emailService = new EmailService();
    $emailEnviado = $emailService->enviarConfirmacionParticipacionJunio($correo, $participanteNombre, $creadas);
    if (!$emailEnviado) {
        $emailError = $emailService->getLastError();
        if (class_exists('AppLogger')) {
            AppLogger::warning('participacion_junio email no enviado', [
                'traceId' => $traceId,
                'correo' => $correo,
                'emailError' => $emailError
            ]);
        }
    } else {
        if (class_exists('AppLogger')) {
            AppLogger::info('participacion_junio email enviado', ['traceId' => $traceId, 'correo' => $correo]);
        }
    }
} else {
    if (class_exists('AppLogger')) {
        AppLogger::warning('participacion_junio sin correo responsable', ['traceId' => $traceId, 'responsable' => $responsableDoc]);
    }
}

jsonResponse([
    'success' => true,
    'creadas' => $creadas,
    'omitidas' => $omitidas,
    'errores' => $errores,
    'emailEnviado' => $emailEnviado,
    'emailError' => $emailError,
    'traceId' => $traceId
]);
