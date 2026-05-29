<?php

function eventosTipo18Config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $path = __DIR__ . '/../config/eventos_tipo18.php';
        $cfg = file_exists($path) ? require $path : [];
    }
    return $cfg;
}

function eventosTipo18FestivegasIds(): array
{
    $cfg = eventosTipo18Config();
    $ids = $cfg['festivegas_curso_ids'] ?? ['1801'];
    return array_map('strval', $ids);
}

function eventosTipo18OpenKewmgangId(): string
{
    $cfg = eventosTipo18Config();
    $id = trim((string) ($cfg['open_kewmgang_curso_id'] ?? '1802'));
    return $id !== '' ? $id : '1802';
}

function eventosTipo18EsFestivegas(string $idCurso): bool
{
    return in_array((string) $idCurso, eventosTipo18FestivegasIds(), true);
}

function eventosTipo18EsOpenKewmgang(string $idCurso): bool
{
    return (string) $idCurso === eventosTipo18OpenKewmgangId();
}
