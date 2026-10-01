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
