<?php

/**
 * Reserva de cupo 2027 — ventanas, categorías de no continuidad y helpers.
 */

function reservaCupoZonaHoraria(): DateTimeZone
{
    return new DateTimeZone('America/Bogota');
}

function reservaCupoAhora(): DateTimeImmutable
{
    return new DateTimeImmutable('now', reservaCupoZonaHoraria());
}

/** Año destino de la preinscripción (enero). */
function reservaCupoAnioDestino(): int
{
    return (int) env('RESERVA_CUPO_ANIO', 2027);
}

/** Mes origen de cursos activos a listar (noviembre). */
function reservaCupoMesOrigen(): string
{
    $mes = preg_replace('/\D/', '', (string) env('RESERVA_CUPO_MES_ORIGEN', '11'));
    return str_pad($mes !== '' ? $mes : '11', 2, '0', STR_PAD_LEFT);
}

function reservaCupoAnioOrigen(): int
{
    return (int) env('RESERVA_CUPO_ANIO_ORIGEN', 2026);
}

function reservaCupoInicio(): DateTimeImmutable
{
    $raw = trim((string) env('RESERVA_CUPO_INICIO', '2026-11-01 00:00:00'));
    return new DateTimeImmutable($raw, reservaCupoZonaHoraria());
}

/** Cierra el 15 de noviembre a media noche (fin del día 15, hora Colombia). */
function reservaCupoFin(): DateTimeImmutable
{
    $raw = trim((string) env('RESERVA_CUPO_FIN', '2026-11-15 23:59:59'));
    return new DateTimeImmutable($raw, reservaCupoZonaHoraria());
}

function reservaCupoHabilitada(): bool
{
    $ahora = reservaCupoAhora();
    return $ahora >= reservaCupoInicio() && $ahora <= reservaCupoFin();
}

function reservaCupoTextoVentana(): string
{
    return 'hasta el 15 de noviembre de 2026 a las 11:59 p. m. (hora Colombia)';
}

function reservaCupoNotaCambioActividad(): string
{
    return 'Los cambios de actividad podrán realizarse del 16 al 29 de noviembre de 2026';
}

/**
 * Categorías de no continuidad (reserva de cupo).
 *
 * @return array<string, string>
 */
function reservaCupoCategoriasNoContinua(): array
{
    return [
        'adaptacion_curso' => 'Adaptación al curso',
        'bullying' => 'Bullying',
        'mayor_nivel_competitivo' => 'Búsqueda de mayor nivel competitivo',
        'carga_academica' => 'Carga académica',
        'cambio_club' => 'Cambio de club',
        'cambio_curso' => 'Cambio de curso',
        'cambio_domicilio' => 'Cambio de domicilio',
        'condicion_medica' => 'Condición médica',
        'costo_curso' => 'Costo del curso',
        'cruce_programas' => 'Cruce de programas',
        'inconformidad_entrenador' => 'Inconformidad con el entrenador',
        'inconformidad_club' => 'Inconformidad con el club',
        'motivos_personales' => 'Motivos personales',
        'metodologia_entrenamiento' => 'Metodología de entrenamiento',
        'problemas_economicos' => 'Problemas económicos',
        'retiro_colegio' => 'Retiro del colegio',
        'viaje' => 'Viaje',
    ];
}

function reservaCupoObservacionContinua(): string
{
    return 'Preinscripción generada en el periodo de reserva de cupos '
        . reservaCupoAnioDestino()
        . '. Formulario reserva de cupo.';
}

function reservaCupoObservacionNoContinua(string $categoriaLabel): string
{
    return 'Retiro — Categoría: ' . $categoriaLabel
        . '. Formulario reserva de cupo ' . reservaCupoAnioDestino() . '.';
}
