<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/reserva_cupo.php';

header('Content-Type: application/json; charset=utf-8');

if (!reservaCupoHabilitada()) {
    jsonResponse([
        'success' => false,
        'error' => reservaCupoAvisoCerrado() . ' ' . reservaCupoNotaCambioActividad(),
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
$estadosValidos = [
    'ACTIVO', 'Activo',
    'CONFIRMADO', 'Confirmado', 'confirmado',
    'INCAPACITADO', 'Incapacitado', 'incapacitado',
];

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
        'cursoPosterior',
        'requierePosterior',
    ], ['ID_Curso' => $idCurso]);
    if (!$row) {
        return [
            'nombre' => $idCurso,
            'sede' => '',
            'curso_posterior' => '',
            'requiere_posterior' => false,
        ];
    }
    $nombre = trim((string) ($row['Nombre_del_curso'] ?? $row['Nombre_Corto_Curso'] ?? $idCurso));
    return [
        'nombre' => $nombre !== '' ? $nombre : $idCurso,
        'sede' => trim((string) ($row['Sede'] ?? '')),
        'curso_posterior' => trim((string) ($row['cursoPosterior'] ?? '')),
        'requiere_posterior' => (int) ($row['requierePosterior'] ?? 0) === 1,
    ];
};

$resolverCursoId = static function (string $ref) use ($database): string {
    $ref = trim($ref);
    if ($ref === '') {
        return '';
    }
    $byId = $database->get('cursos_2025', 'ID_Curso', ['ID_Curso' => $ref]);
    if ($byId) {
        return (string) $byId;
    }
    $byNombre = $database->get('cursos_2025', 'ID_Curso', [
        'Nombre_del_curso' => $ref,
        'LIMIT' => 1,
    ]);
    return $byNombre ? (string) $byNombre : '';
};

// Una fila por IDCurso actual (inscripción más reciente del mes origen).
// El mes origen solo define en qué cursos está hoy; la recomendación se
// resuelve con todo el año (sep/oct/nov/…), no con un mes fijo.
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

// Recs del año por curso actual: [IDCurso => list of [id, rec]]
$recsPorCurso = [];
$idsCursoActual = array_keys($porCursoActual);
if ($idsCursoActual !== []) {
    $recsAnio = $database->select('inscripciones_1', [
        'IDCurso',
        'cursoRecomendado',
        'IDInscripcion',
    ], [
        'validador_participante' => $participanteDoc,
        'año' => $anioOrigen,
        'Tipo' => 1,
        'IDCurso' => $idsCursoActual,
        'ORDER' => ['IDInscripcion' => 'DESC'],
    ]) ?: [];

    foreach ($recsAnio as $rr) {
        $cid = (string) ($rr['IDCurso'] ?? '');
        if ($cid === '' || !isset($porCursoActual[$cid])) {
            continue;
        }
        $rec = trim((string) ($rr['cursoRecomendado'] ?? ''));
        if ($rec === '') {
            continue;
        }
        $recsPorCurso[$cid][] = [
            'id' => (int) ($rr['IDInscripcion'] ?? 0),
            'rec' => $rec,
        ];
    }
}

/**
 * Elige recomendación del año (cualquier mes):
 * 1) Si hay varias distintas y una coincide con cursoPosterior → esa (catálogo recién actualizado).
 * 2) Si no, la distinta al curso actual con IDInscripcion más alto.
 * 3) Si no hay distinta, cualquier rec del año (más reciente).
 * 4) Luego cursoPosterior del catálogo si sigue vacío o = actual.
 */
$elegirRecomendadoAnio = static function (
    string $cursoActualId,
    array $recsAnioCurso,
    string $posteriorRef,
    callable $resolverCursoId
): string {
    $distintas = []; // recId => max IDInscripcion
    $mejorCualquiera = '';
    $mejorCualquieraId = -1;

    foreach ($recsAnioCurso as $item) {
        $recRaw = (string) ($item['rec'] ?? '');
        $insId = (int) ($item['id'] ?? 0);
        $recId = $resolverCursoId($recRaw);
        if ($recId === '') {
            $recId = $recRaw;
        }
        if ($insId > $mejorCualquieraId) {
            $mejorCualquiera = $recId;
            $mejorCualquieraId = $insId;
        }
        if ($recId === $cursoActualId) {
            continue;
        }
        if (!isset($distintas[$recId]) || $insId > $distintas[$recId]) {
            $distintas[$recId] = $insId;
        }
    }

    $posteriorId = '';
    if ($posteriorRef !== '') {
        $posteriorId = $resolverCursoId($posteriorRef);
    }

    if ($distintas !== []) {
        // Varias recomendaciones en el año: si el catálogo (posterior) está entre ellas, gana.
        if ($posteriorId !== '' && $posteriorId !== $cursoActualId && isset($distintas[$posteriorId])) {
            return $posteriorId;
        }
        // Si no, la más reciente (mayor IDInscripcion).
        arsort($distintas, SORT_NUMERIC);
        return (string) array_key_first($distintas);
    }

    if ($mejorCualquiera !== '' && $mejorCualquiera !== $cursoActualId) {
        return $mejorCualquiera;
    }

    if ($posteriorId !== '') {
        return $posteriorId;
    }

    if ($mejorCualquiera !== '') {
        return $mejorCualquiera;
    }

    return $cursoActualId;
};

// Una sola decisión por curso recomendado 2027
// (varios cursos actuales pueden apuntar al mismo recomendado).
$porRecomendado = [];
foreach ($porCursoActual as $cursoActualId => $r) {
    $cursoActualId = (string) $cursoActualId;
    $metaActual = $nombreCursoPorId($cursoActualId);
    $nombreActual = $metaActual['nombre'];

    $recomendadoId = $elegirRecomendadoAnio(
        $cursoActualId,
        $recsPorCurso[$cursoActualId] ?? [],
        trim((string) ($metaActual['curso_posterior'] ?? '')),
        $resolverCursoId
    );
    // Nota: castear IDs a string — PHP convierte keys numéricas a int y "148" === 148 falla.
    if ($recomendadoId === '') {
        $recomendadoId = $cursoActualId;
    }

    // Igual que Gestión: si el curso recomendado exige posterior, usar ese
    // (p. ej. MENORES SELECCIONADO → MENORES).
    $metaRecFlag = $nombreCursoPorId($recomendadoId);
    if (!empty($metaRecFlag['requiere_posterior'])) {
        $postRec = trim((string) ($metaRecFlag['curso_posterior'] ?? ''));
        if ($postRec !== '') {
            $desdePostRec = $resolverCursoId($postRec);
            if ($desdePostRec !== '' && $desdePostRec !== $recomendadoId) {
                $recomendadoId = $desdePostRec;
            }
        }
    }

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
