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

$nombreCursoPorId = static function (string $idCurso) use ($database): array {
    $row = $database->get('cursos_2025', [
        'Nombre_del_curso',
        'Nombre_Corto_Curso',
        'Sede',
    ], ['ID_Curso' => $idCurso]);
    if (!$row) {
        return ['nombre' => $idCurso, 'sede' => ''];
    }
    $nombre = trim((string) ($row['Nombre_del_curso'] ?? $row['Nombre_Corto_Curso'] ?? $idCurso));
    return [
        'nombre' => $nombre !== '' ? $nombre : $idCurso,
        'sede' => trim((string) ($row['Sede'] ?? '')),
    ];
};

// Una fila por IDCurso actual (si se repite el mismo curso, se queda la inscripción más reciente)
$porCursoActual = [];
foreach ($rows as $r) {
    $cid = (string) ($r['IDCurso'] ?? '');
    if ($cid === '') {
        continue;
    }
    if (!isset($porCursoActual[$cid])) {
        $porCursoActual[$cid] = $r;
    }
}

// Una sola decisión por curso recomendado 2027
// (varios cursos actuales pueden apuntar al mismo recomendado).
$porRecomendado = [];
foreach ($porCursoActual as $r) {
    $cursoActualId = (string) $r['IDCurso'];
    $recomendadoId = trim((string) ($r['cursoRecomendado'] ?? ''));
    if ($recomendadoId === '') {
        $recomendadoId = $cursoActualId;
    }

    // Nombre real desde catálogo (nombreCurso en inscripción a veces viene mal/replicado)
    $metaActual = $nombreCursoPorId($cursoActualId);
    $nombreActual = $metaActual['nombre'];

    if (isset($porRecomendado[$recomendadoId])) {
        $prev = &$porRecomendado[$recomendadoId];
        if ($nombreActual !== '' && !in_array($nombreActual, $prev['_nombres_actuales'], true)) {
            $prev['_nombres_actuales'][] = $nombreActual;
        }
        if ($cursoActualId !== '' && !in_array($cursoActualId, $prev['_ids_actuales'], true)) {
            $prev['_ids_actuales'][] = $cursoActualId;
        }
        unset($prev);
        continue;
    }

    $metaRec = $nombreCursoPorId($recomendadoId);
    $nombreRecomendado = $metaRec['nombre'];

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

    $sede = trim((string) ($r['Sede'] ?? ''));
    if ($sede === '') {
        $sede = $metaActual['sede'] !== '' ? $metaActual['sede'] : $metaRec['sede'];
    }

    $porRecomendado[$recomendadoId] = [
        'inscripcion_id' => (int) ($r['IDInscripcion'] ?? 0),
        'curso_actual_id' => $cursoActualId,
        'curso_actual_nombre' => $nombreActual,
        '_nombres_actuales' => [$nombreActual],
        '_ids_actuales' => [$cursoActualId],
        'sede' => $sede,
        'transporte' => $r['Transporte'] ?? null,
        'responsable_documento' => $r['validador_responsable'] ?? ($rowPart['IDResponsable'] ?? ''),
        'curso_recomendado_id' => $recomendadoId,
        'curso_recomendado_nombre' => $nombreRecomendado,
        'ya_reservado' => $yaReservado,
        'ya_no_continua' => $yaNoContinua,
    ];
}

$items = [];
foreach ($porRecomendado as $item) {
    $nombres = $item['_nombres_actuales'] ?? [];
    unset($item['_nombres_actuales'], $item['_ids_actuales']);
    if (count($nombres) > 1) {
        $item['curso_actual_nombre'] = implode(' · ', $nombres);
    }
    $items[] = $item;
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
