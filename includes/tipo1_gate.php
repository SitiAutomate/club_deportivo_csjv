<?php

/**
 * Gate de tipo 1 (cursos): restringe inscripción de participantes nuevos
 * durante una ventana configurable por .env.
 *
 * Antiguo = tuvo inscripción tipo 1 activa en noviembre del año configurado
 * (por defecto noviembre del año inmediatamente anterior al ciclo).
 */

function tipo1GateZonaHoraria(): DateTimeZone
{
    return new DateTimeZone('America/Bogota');
}

function tipo1GateAhora(): DateTimeImmutable
{
    return new DateTimeImmutable('now', tipo1GateZonaHoraria());
}

function tipo1GateEnabled(): bool
{
    $v = strtolower(trim((string) env('TIPO1_GATE_ENABLED', 'false')));
    return in_array($v, ['1', 'true', 'yes', 'on'], true);
}

function tipo1GateSoloAntiguosDesde(): ?DateTimeImmutable
{
    $raw = trim((string) env('TIPO1_SOLO_ANTIGUOS_DESDE', ''));
    if ($raw === '') {
        return null;
    }
    return new DateTimeImmutable($raw . (strlen($raw) === 10 ? ' 00:00:00' : ''), tipo1GateZonaHoraria());
}

function tipo1GateNuevosDesde(): ?DateTimeImmutable
{
    $raw = trim((string) env('TIPO1_NUEVOS_DESDE', ''));
    if ($raw === '') {
        return null;
    }
    return new DateTimeImmutable($raw . (strlen($raw) === 10 ? ' 00:00:00' : ''), tipo1GateZonaHoraria());
}

/** Mes de referencia para considerar “antiguo” (noviembre). */
function tipo1GateAntiguoMes(): string
{
    $mes = preg_replace('/\D/', '', (string) env('TIPO1_ANTIGUO_MES', '11'));
    return str_pad($mes !== '' ? $mes : '11', 2, '0', STR_PAD_LEFT);
}

/**
 * Año del noviembre de referencia.
 * Por defecto: año inmediatamente anterior al año destino de cursos,
 * o el configurado en TIPO1_ANTIGUO_ANIO.
 */
function tipo1GateAntiguoAnio(): int
{
    $cfg = trim((string) env('TIPO1_ANTIGUO_ANIO', ''));
    if ($cfg !== '' && ctype_digit($cfg)) {
        return (int) $cfg;
    }
    // Si estamos en nov/dic del año Y, el noviembre de referencia es Y; si no, Y-1.
    $ahora = tipo1GateAhora();
    $y = (int) $ahora->format('Y');
    $m = (int) $ahora->format('n');
    return $m >= 11 ? $y : ($y - 1);
}

/**
 * ¿Estamos en la ventana donde solo antiguos ven tipo 1?
 */
function tipo1GateSoloAntiguosActivo(): bool
{
    if (!tipo1GateEnabled()) {
        return false;
    }
    $desde = tipo1GateSoloAntiguosDesde();
    $nuevosDesde = tipo1GateNuevosDesde();
    if ($desde === null || $nuevosDesde === null) {
        return false;
    }
    $ahora = tipo1GateAhora();
    return $ahora >= $desde && $ahora < $nuevosDesde;
}

/**
 * ¿El participante tuvo cursos activos en el noviembre de referencia?
 */
function tipo1GateEsAntiguo($database, string $participanteDoc): bool
{
    $doc = trim($participanteDoc);
    if ($doc === '') {
        return false;
    }
    $estadosValidos = ['ACTIVO', 'Confirmado', 'confirmado', 'Incapacitado', 'incapacitado', 'Activo'];
    $count = (int) $database->count('inscripciones_1', [
        'validador_participante' => $doc,
        'Mes' => tipo1GateAntiguoMes(),
        'año' => tipo1GateAntiguoAnio(),
        'Tipo' => 1,
        'Estado' => $estadosValidos,
    ]);
    return $count > 0;
}

/**
 * Si puede ver/inscribirse en tipo 1 en el formulario normal.
 */
function tipo1GatePuedeVerTipo1($database, string $participanteDoc): bool
{
    if (!tipo1GateSoloAntiguosActivo()) {
        return true;
    }
    return tipo1GateEsAntiguo($database, $participanteDoc);
}

/**
 * ¿Es nuevo en el club (sin ninguna inscripción previa de cursos tipo 1)?
 * Se evalúa antes de crear la inscripción actual.
 */
function tipo1EsNuevoIngreso($database, string $participanteDoc): bool
{
    $doc = trim($participanteDoc);
    if ($doc === '') {
        return true;
    }
    $count = (int) $database->count('inscripciones_1', [
        'validador_participante' => $doc,
        'Tipo' => 1,
    ]);
    return $count === 0;
}

/** Texto que se guarda en OBSERVACION para nuevos ingresos tipo 1. */
function tipo1ObservacionNuevoIngreso(): string
{
    return 'Nuevo ingreso';
}

/**
 * Une la marca de nuevo ingreso con una observación previa (si la hay).
 */
function tipo1MergeObservacionNuevoIngreso(?string $observacionActual): string
{
    $marca = tipo1ObservacionNuevoIngreso();
    $prev = trim((string) ($observacionActual ?? ''));
    if ($prev === '') {
        return $marca;
    }
    if (stripos($prev, $marca) !== false) {
        return $prev;
    }
    return $prev . ' | ' . $marca;
}

/**
 * Desde TIPO1_NUEVOS_DESDE el formulario de cursos usa enero (ciclo nuevo)
 * como mes por defecto en lugar de mes actual + siguiente.
 */
function tipo1DebeUsarEneroDefault(): bool
{
    $desde = tipo1GateNuevosDesde();
    if ($desde === null) {
        return false;
    }
    return tipo1GateAhora() >= $desde;
}

/**
 * Año de inscripción para el formulario tipo 1.
 * Si ya aplica enero default y NUEVOS_DESDE cae en nov/dic, es el año siguiente.
 */
function tipo1AnioInscripcionDefault(): int
{
    $ahora = tipo1GateAhora();
    if (!tipo1DebeUsarEneroDefault()) {
        return (int) $ahora->format('Y');
    }
    $desde = tipo1GateNuevosDesde();
    if ($desde === null) {
        return (int) $ahora->format('Y');
    }
    $y = (int) $desde->format('Y');
    $m = (int) $desde->format('n');
    return $m >= 11 ? $y + 1 : $y;
}

/**
 * Meses a ofrecer en el selector tipo 1.
 * Desde TIPO1_NUEVOS_DESDE (p. ej. dic): solo Enero.
 * En enero del ciclo nuevo: Enero y Febrero.
 * Antes de NUEVOS_DESDE: mes actual + siguiente.
 *
 * @return list<string> NumMes p. ej. ['01'] o ['01','02']
 */
function tipo1MesesSelector(): array
{
    $ahora = tipo1GateAhora();
    $mesActual = (int) $ahora->format('n');

    if (tipo1DebeUsarEneroDefault()) {
        // Solo en enero del año de inscripción se ofrece también febrero.
        $anioInscripcion = tipo1AnioInscripcionDefault();
        $anioAhora = (int) $ahora->format('Y');
        if ($mesActual === 1 && $anioAhora === $anioInscripcion) {
            return ['01', '02'];
        }
        return ['01'];
    }

    $mesSiguiente = $mesActual >= 12 ? 1 : $mesActual + 1;
    return [
        str_pad((string) $mesActual, 2, '0', STR_PAD_LEFT),
        str_pad((string) $mesSiguiente, 2, '0', STR_PAD_LEFT),
    ];
}

/** Mes preseleccionado en el formulario tipo 1. */
function tipo1MesDefault(): string
{
    $meses = tipo1MesesSelector();
    return $meses[0] ?? '01';
}

/**
 * Normaliza nombres de curso para resolver cursoPosterior con espacios/guiones/tildes distintos.
 */
function tipo1NormalizarNombreCurso(string $s): string
{
    $s = trim($s);
    if ($s === '') {
        return '';
    }
    $s = str_replace(["\xC2\xA0", '–', '—', '−'], [' ', '-', '-', '-'], $s);
    if (function_exists('mb_strtoupper')) {
        $s = mb_strtoupper($s, 'UTF-8');
    } else {
        $s = strtoupper($s);
    }
    $s = strtr($s, [
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
    ]);
    $s = preg_replace('/\s*-\s*/', '-', $s) ?? $s;
    $s = preg_replace('/\s+/', ' ', $s) ?? $s;
    return trim($s);
}

/**
 * Resuelve una referencia (ID o nombre) a ID_Curso en cursos_2025.
 */
function tipo1ResolverCursoId($database, string $ref): string
{
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
    if ($byNombre) {
        return (string) $byNombre;
    }

    static $mapaNorm = null;
    if ($mapaNorm === null) {
        $mapaNorm = [];
        $todos = $database->select('cursos_2025', ['ID_Curso', 'Nombre_del_curso']) ?: [];
        foreach ($todos as $cRow) {
            $nom = trim((string) ($cRow['Nombre_del_curso'] ?? ''));
            if ($nom === '') {
                continue;
            }
            $key = tipo1NormalizarNombreCurso($nom);
            if ($key === '' || isset($mapaNorm[$key])) {
                continue;
            }
            $mapaNorm[$key] = (string) $cRow['ID_Curso'];
        }
    }
    $keyRef = tipo1NormalizarNombreCurso($ref);
    return ($keyRef !== '' && isset($mapaNorm[$keyRef])) ? $mapaNorm[$keyRef] : '';
}

/**
 * ¿Debe mostrar cursos posteriores en el listado tipo 1?
 * Aplica en noviembre y diciembre, o desde TIPO1_NUEVOS_DESDE.
 */
function tipo1DebeMostrarPosterioresFinAnio(): bool
{
    if (tipo1DebeUsarEneroDefault()) {
        return true;
    }
    $mesActual = (int) tipo1GateAhora()->format('n');
    return in_array($mesActual, [11, 12], true);
}

/**
 * Fin de año (nov/dic o desde TIPO1_NUEVOS_DESDE): listar el cursoPosterior de cada curso activo
 * del filtro; si el posterior existe como curso ACTIVO se usa ese curso, si es solo texto
 * (aún no trocado en catálogo) se mantiene el ID activo mostrando el nombre de cursoPosterior,
 * y si no tiene posterior se deja el activo tal cual.
 *
 * @param list<array<string,mixed>> $cursos
 * @return list<array<string,mixed>>
 */
function tipo1MapearCursosAPosterioresFinAnio($database, array $cursos): array
{
    if ($cursos === []) {
        return [];
    }

    $idsOrigen = [];
    foreach ($cursos as $c) {
        $id = trim((string) ($c['ID_Curso'] ?? $c['id'] ?? ''));
        if ($id !== '') {
            $idsOrigen[$id] = true;
        }
    }
    if ($idsOrigen === []) {
        return $cursos;
    }

    $metaOrigen = $database->select('cursos_2025', [
        'ID_Curso',
        'cursoPosterior',
        'Estado_del_curso',
    ], [
        'ID_Curso' => array_keys($idsOrigen),
    ]) ?: [];

    $candidatos = [];
    $idsResolverCheck = [];
    foreach ($metaOrigen as $row) {
        $id = trim((string) ($row['ID_Curso'] ?? ''));
        if ($id === '') {
            continue;
        }
        $post = trim((string) ($row['cursoPosterior'] ?? ''));
        $resolved = $post !== '' ? tipo1ResolverCursoId($database, $post) : '';
        if ($resolved !== '' && $resolved !== $id) {
            $candidatos[$id] = $resolved;
            $idsResolverCheck[$resolved] = true;
        } else {
            $candidatos[$id] = $id;
        }
    }

    $activosMap = [];
    if ($idsResolverCheck !== []) {
        $rowsAct = $database->select('cursos_2025', ['ID_Curso', 'Estado_del_curso'], [
            'ID_Curso' => array_keys($idsResolverCheck),
        ]) ?: [];
        foreach ($rowsAct as $ra) {
            $rid = trim((string) ($ra['ID_Curso'] ?? ''));
            $activosMap[$rid] = strtoupper(trim((string) ($ra['Estado_del_curso'] ?? ''))) === 'ACTIVO';
        }
    }

    $idsFinales = [];
    $nombreOverridePorId = []; // ID_Curso => nombre cursoPosterior cuando es texto sin curso propio
    foreach ($metaOrigen as $row) {
        $id = trim((string) ($row['ID_Curso'] ?? ''));
        if ($id === '' || !isset($candidatos[$id])) {
            continue;
        }
        $destino = $candidatos[$id];
        if ($destino !== $id && !empty($activosMap[$destino])) {
            $idsFinales[$destino] = true;
            continue;
        }
        // Sin otro curso posterior activo: mantener el activo actual,
        // pero si tiene texto en cursoPosterior mostrar ese nombre.
        if (strtoupper(trim((string) ($row['Estado_del_curso'] ?? ''))) === 'ACTIVO') {
            $idsFinales[$id] = true;
            $post = trim((string) ($row['cursoPosterior'] ?? ''));
            if ($post !== '') {
                $nombreOverridePorId[$id] = $post;
            }
        }
    }

    if ($idsFinales === []) {
        return [];
    }

    $finales = $database->select('cursos_2025', [
        'ID_Curso',
        'Nombre_del_curso',
        'Nombre_Corto_Curso',
        'Tarifa_Curso',
        'Cupos_maximos',
        'Sede',
        'Linea',
        'Actividad',
        'Tipo',
        'Fecha_Inicio',
        'Fecha_Final',
        'Estado_del_curso',
    ], [
        'ID_Curso' => array_keys($idsFinales),
        'Estado_del_curso' => 'ACTIVO',
        'ORDER' => ['Nombre_del_curso' => 'ASC'],
    ]) ?: [];

    foreach ($finales as &$f) {
        $fid = trim((string) ($f['ID_Curso'] ?? ''));
        if ($fid !== '' && isset($nombreOverridePorId[$fid])) {
            $f['Nombre_del_curso'] = $nombreOverridePorId[$fid];
            $f['Nombre_Corto_Curso'] = $nombreOverridePorId[$fid];
        }
    }
    unset($f);

    usort($finales, static function ($a, $b) {
        return strcasecmp(
            (string) ($a['Nombre_del_curso'] ?? ''),
            (string) ($b['Nombre_del_curso'] ?? '')
        );
    });

    return $finales;
}
