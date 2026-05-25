<?php

function participacionJunioZonaHoraria(): DateTimeZone
{
    return new DateTimeZone('America/Bogota');
}

function participacionJunioTextoVentana(): string
{
    return 'del 25 al 26 de mayo de 2026';
}

function participacionJunioObservacion(string $participara): string
{
    return $participara === 'Sí' ? 'Sí participará' : 'No participará';
}

function participacionJunioHabilitada(): bool
{
    $tz = participacionJunioZonaHoraria();
    $ahora = new DateTimeImmutable('now', $tz);
    $inicio = new DateTimeImmutable('2026-05-25 00:00:00', $tz);
    $fin = new DateTimeImmutable('2026-05-26 23:59:59', $tz);

    return $ahora >= $inicio && $ahora <= $fin;
}
