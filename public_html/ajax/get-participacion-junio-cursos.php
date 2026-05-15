<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_once __DIR__ . '/../../includes/participacion_junio.php';

header('Content-Type: application/json; charset=utf-8');

if (!participacionJunioHabilitada()) {
    jsonResponse(['success' => false, 'error' => 'El formulario no está disponible en este momento.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfValidate();
}

$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? getPostData() : $_GET;
$documento = trim($input['documento'] ?? $input['participante_id'] ?? '');
$anio = (int) ($input['anio'] ?? date('Y'));

if ($documento === '') {
    jsonResponse(['success' => false, 'error' => 'Ingrese el documento del participante'], 400);
}

$validacion = validarDocumentoParticipante($documento);
if (!$validacion['valid']) {
    jsonResponse(['success' => false, 'error' => $validacion['error']], 400);
}
$documento = normalizarDocumentoParticipante($documento);

$participanteModel = new Participante($database);
$participante = $participanteModel->getByDocumento($documento);
if (!$participante) {
    jsonResponse(['success' => false, 'error' => 'No encontramos un participante registrado con ese documento.'], 404);
}

$mesActual = str_pad((string) date('n'), 2, '0', STR_PAD_LEFT);
$inscripcionModel = new Inscripcion($database);
$rows = $inscripcionModel->getCursosActivosParticipante($documento, $mesActual, $anio, 1);

$nombresMes = [
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
];

$porCurso = [];
foreach ($rows as $row) {
    $idCurso = (string) ($row['IDCurso'] ?? '');
    if ($idCurso === '' || isset($porCurso[$idCurso])) {
        continue;
    }
    $bloqueado = $inscripcionModel->tieneInscripcionJunioBloqueada($documento, $idCurso, $anio, 1);
    $porCurso[$idCurso] = [
        'id' => $idCurso,
        'nombre' => $row['nombreCurso'] ?? $idCurso,
        'mes' => $row['Mes'] ?? $mesActual,
        'mes_nombre' => $nombresMes[$row['Mes'] ?? ''] ?? ($row['Mes'] ?? ''),
        'sede' => $row['Sede'] ?? '',
        'estado' => $row['Estado'] ?? '',
        'bloqueado_junio' => $bloqueado,
        'motivo_bloqueo' => $bloqueado
            ? 'Ya tiene participación registrada para vacaciones en este curso.'
            : null
    ];
}

$participanteNombre = $participante['Nombre_Completo']
    ?? trim(($participante['Primer_Nombre'] ?? '') . ' ' . ($participante['Primer_Apellido'] ?? ''));

jsonResponse([
    'success' => true,
    'participante' => [
        'documento' => $participante['IDParticipante'],
        'nombre' => $participanteNombre
    ],
    'mes_consulta' => $mesActual,
    'mes_consulta_nombre' => $nombresMes[$mesActual] ?? $mesActual,
    'cursos' => array_values($porCurso)
]);
