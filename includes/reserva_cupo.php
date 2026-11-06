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
    $raw = trim((string) env('RESERVA_CUPO_INICIO', '2026-11-17 00:00:00'));
    return new DateTimeImmutable($raw, reservaCupoZonaHoraria());
}

/** Cierra el 5 de diciembre a media noche (fin del día 5, hora Colombia). */
function reservaCupoFin(): DateTimeImmutable
{
    $raw = trim((string) env('RESERVA_CUPO_FIN', '2026-12-05 23:59:59'));
    return new DateTimeImmutable($raw, reservaCupoZonaHoraria());
}

function reservaCupoHabilitada(): bool
{
    $ahora = reservaCupoAhora();
    return $ahora >= reservaCupoInicio() && $ahora <= reservaCupoFin();
}

function reservaCupoTextoVentana(): string
{
    return 'hasta el 5 de diciembre de 2026';
}

function reservaCupoNotaCambioActividad(): string
{
    return 'A partir del 6 de diciembre de 2026, el formulario se habilitará para los cambios de disciplinas y para los nuevos deportistas.';
}

function reservaCupoAvisoCerrado(): string
{
    return 'El formulario de inscripciones para '
        . reservaCupoAnioDestino()
        . ' estará disponible del 17 de noviembre al 5 de diciembre de 2026, exclusivamente para los deportistas actuales del Club Deportivo.';
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
    return 'Retiro — Motivo: ' . $categoriaLabel
        . '. Formulario reserva de cupo ' . reservaCupoAnioDestino() . '.';
}
