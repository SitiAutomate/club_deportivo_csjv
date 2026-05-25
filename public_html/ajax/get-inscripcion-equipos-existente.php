<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfValidate();
}

$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? getPostData() : $_GET;
$documentoResponsable = trim((string) ($input['responsable_documento'] ?? $input['responsable_id'] ?? ''));
$idCurso = trim((string) ($input['curso_id'] ?? $input['IDCurso'] ?? ''));
$anio = (int) ($input['anio'] ?? $input['año'] ?? date('Y'));
$tipoId = (int) ($input['tipo_id'] ?? 18);

if ($documentoResponsable === '' || $idCurso === '') {
    jsonResponse(['success' => false, 'error' => 'Faltan datos para consultar.'], 400);
}

$inscripcionModel = new Inscripcion($database);
$inscripcion = $inscripcionModel->getPorResponsableYCurso($documentoResponsable, $idCurso, $anio, $tipoId);

if (!$inscripcion) {
    jsonResponse(['success' => true, 'exists' => false]);
}

$idInscripcion = (int) $inscripcion['IDInscripcion'];
$equipoModel = new Equipo($database);
$deportistaModel = new EquipoDeportista($database);

$equipos = $equipoModel->getByInscripcion($idInscripcion);
$equiposPayload = array_map(function ($eq) use ($deportistaModel) {
    return [
        'id_equipo' => (int) ($eq['IDEquipo'] ?? 0),
        'nombre_equipo' => $eq['nombre_equipo'] ?? '',
        'rama' => $eq['rama'] ?? '',
        'categoria' => $eq['categoria'] ?? '',
        'entrenador_nombre' => $eq['entrenador_nombre'] ?? '',
        'entrenador_documento' => $eq['entrenador_documento'] ?? '',
        'entrenador_contacto' => $eq['entrenador_contacto'] ?? '',
        'asistente_nombre' => $eq['asistente_nombre'] ?? '',
        'asistente_documento' => $eq['asistente_documento'] ?? '',
        'asistente_contacto' => $eq['asistente_contacto'] ?? '',
        'deportistas' => array_map(function ($d) {
            return [
                'nombre_completo' => $d['nombre_completo'] ?? '',
                'fecha_nacimiento' => $d['fecha_nacimiento'] ?? '',
                'documento' => $d['documento'] ?? '',
            ];
        }, $deportistaModel->getByEquipo((int) ($eq['IDEquipo'] ?? 0)))
    ];
}, $equipos);

jsonResponse([
    'success' => true,
    'exists' => true,
    'inscripcion_id' => $idInscripcion,
    'estado' => $inscripcion['Estado'] ?? '',
    'fecha_inscripcion' => $inscripcion['Fecha_Inscripción'] ?? '',
    'total_equipos' => count($equiposPayload),
    'equipos' => $equiposPayload,
]);
