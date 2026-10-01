<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/reserva_cupo.php';

header('Content-Type: application/json; charset=utf-8');

if (!reservaCupoHabilitada()) {
    jsonResponse([
        'success' => false,
        'error' => 'El formulario de reserva de cupo ya no está disponible. Estuvo abierto ' . reservaCupoTextoVentana() . '.',
        'habilitado' => false,
    ], 403);
}

$participanteDoc = trim($_GET['participante_id'] ?? $_GET['documento'] ?? '');
if ($participanteDoc === '') {
    jsonResponse(['success' => false, 'error' => 'Documento del participante requerido'], 400);
}

$participante = new Participante($database);
$rowPart = $participante->getByDocumento($participanteDoc);
if (!$rowPart) {
    jsonResponse(['success' => false, 'error' => 'Participante no encontrado. Debe estar registrado previamente.'], 404);
}

$mesOrigen = reservaCupoMesOrigen();
$anioOrigen = reservaCupoAnioOrigen();
$anioDestino = reservaCupoAnioDestino();
$estadosValidos = ['ACTIVO', 'Confirmado', 'confirmado', 'Incapacitado', 'incapacitado', 'Activo'];

$rows = $database->select('inscripciones_1', [
    'IDInscripcion',
    'IDCurso',
    'nombreCurso',
    'Mes',
    'Sede',
    'Estado',
    'validador_responsable',
    'cursoRecomendado',
    'Transporte',
], [
    'validador_participante' => $participanteDoc,
    'Mes' => $mesOrigen,
    'año' => $anioOrigen,
    'Tipo' => 1,
    'Estado' => $estadosValidos,
    'ORDER' => ['IDInscripcion' => 'DESC'],
]) ?: [];

// Una fila por curso (si hay duplicados, se queda la de mayor IDInscripcion)
$porCurso = [];
foreach ($rows as $r) {
    $cid = (string) ($r['IDCurso'] ?? '');
    if ($cid === '') {
        continue;
    }
    if (!isset($porCurso[$cid])) {
        $porCurso[$cid] = $r;
    }
}

$items = [];
foreach ($porCurso as $r) {
    $cursoActualId = (string) $r['IDCurso'];
    $recomendadoId = trim((string) ($r['cursoRecomendado'] ?? ''));
    if ($recomendadoId === '') {
        $recomendadoId = $cursoActualId;
    }

    $nombreRecomendado = $recomendadoId;
    $rowCurso = $database->get('cursos_2025', [
        'Nombre_del_curso',
        'Nombre_Corto_Curso',
        'Sede',
    ], ['ID_Curso' => $recomendadoId]);
    if ($rowCurso) {
        $nombreRecomendado = trim((string) ($rowCurso['Nombre_del_curso'] ?? $rowCurso['Nombre_Corto_Curso'] ?? $recomendadoId));
    }

    // Solo "Continúo" (ACTIVO) cuenta como cupo reservado.
    $yaReservado = (int) $database->count('inscripciones_1', [
        'validador_participante' => $participanteDoc,
        'IDCurso' => $recomendadoId,
        'Mes' => '01',
        'año' => $anioDestino,
        'Tipo' => 1,
        'Estado' => ['ACTIVO', 'Activo'],
    ]) > 0;

    $yaNoContinua = (int) $database->count('inscripciones_1', [
        'validador_participante' => $participanteDoc,
        'IDCurso' => $recomendadoId,
        'Mes' => '01',
        'año' => $anioDestino,
        'Tipo' => 1,
        'Estado' => ['RETIRADO', 'Retirado', 'retirado'],
    ]) > 0;

    $items[] = [
        'inscripcion_id' => (int) ($r['IDInscripcion'] ?? 0),
        'curso_actual_id' => $cursoActualId,
        'curso_actual_nombre' => $r['nombreCurso'] ?? $cursoActualId,
        'sede' => $r['Sede'] ?? ($rowCurso['Sede'] ?? ''),
        'transporte' => $r['Transporte'] ?? null,
        'responsable_documento' => $r['validador_responsable'] ?? ($rowPart['IDResponsable'] ?? ''),
        'curso_recomendado_id' => $recomendadoId,
        'curso_recomendado_nombre' => $nombreRecomendado,
        'ya_reservado' => $yaReservado,
        'ya_no_continua' => $yaNoContinua,
    ];
}

jsonResponse([
    'success' => true,
    'habilitado' => true,
    'participante' => [
        'documento' => $rowPart['IDParticipante'],
        'nombre' => $rowPart['Nombre_Completo'] ?? trim(($rowPart['Primer_Nombre'] ?? '') . ' ' . ($rowPart['Primer_Apellido'] ?? '')),
        'responsable_documento' => $rowPart['IDResponsable'] ?? '',
    ],
    'anio_destino' => $anioDestino,
    'mes_origen' => $mesOrigen,
    'anio_origen' => $anioOrigen,
    'categorias' => reservaCupoCategoriasNoContinua(),
    'cursos' => $items,
]);
