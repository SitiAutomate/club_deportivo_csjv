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

function eventosTipo18PrincipalIds(): array
{
    $cfg = eventosTipo18Config();
    $ids = $cfg['principal_curso_ids'] ?? ['1802', '1803', '1804', '1805', '1806'];
    return array_map('strval', $ids);
}

function eventosTipo18OpenKewmgangId(): string
{
    $cfg = eventosTipo18Config();
    $id = trim((string) ($cfg['open_kewmgang_curso_id'] ?? '1802'));
    return $id !== '' ? $id : '1802';
}

function eventosTipo18MedCheerId(): string
{
    $cfg = eventosTipo18Config();
    $id = trim((string) ($cfg['med_cheer_curso_id'] ?? '1803'));
    return $id !== '' ? $id : '1803';
}

function eventosTipo18TkdNacionalId(): string
{
    $cfg = eventosTipo18Config();
    $id = trim((string) ($cfg['tkd_nacional_curso_id'] ?? '1804'));
    return $id !== '' ? $id : '1804';
}

function eventosTipo18CopaFloresId(): string
{
    $cfg = eventosTipo18Config();
    $id = trim((string) ($cfg['copa_flores_curso_id'] ?? '1805'));
    return $id !== '' ? $id : '1805';
}

function eventosTipo18VolleyballFestId(): string
{
    $cfg = eventosTipo18Config();
    $id = trim((string) ($cfg['volleyball_fest_curso_id'] ?? '1806'));
    return $id !== '' ? $id : '1806';
}

function eventosTipo18EsFestivegas(string $idCurso): bool
{
    return in_array((string) $idCurso, eventosTipo18FestivegasIds(), true);
}

function eventosTipo18EsPrincipal(string $idCurso): bool
{
    return in_array((string) $idCurso, eventosTipo18PrincipalIds(), true)
        && !eventosTipo18EsFestivegas($idCurso);
}

function eventosTipo18EsOpenKewmgang(string $idCurso): bool
{
    return (string) $idCurso === eventosTipo18OpenKewmgangId();
}

function eventosTipo18EsMedCheer(string $idCurso): bool
{
    return (string) $idCurso === eventosTipo18MedCheerId();
}

function eventosTipo18EsTkdNacional(string $idCurso): bool
{
    return (string) $idCurso === eventosTipo18TkdNacionalId();
}

function eventosTipo18EsCopaFlores(string $idCurso): bool
{
    return (string) $idCurso === eventosTipo18CopaFloresId();
}

function eventosTipo18EsVolleyballFest(string $idCurso): bool
{
    return (string) $idCurso === eventosTipo18VolleyballFestId();
}

function eventosTipo18MedCheerConfig(): array
{
    return eventosTipo18Config()['med_cheer'] ?? [];
}

function eventosTipo18TkdNacionalConfig(): array
{
    return eventosTipo18Config()['tkd_nacional'] ?? [];
}

function eventosTipo18CopaFloresConfig(): array
{
    return eventosTipo18Config()['copa_flores'] ?? [];
}

function eventosTipo18VolleyballFestConfig(): array
{
    return eventosTipo18Config()['volleyball_fest'] ?? [];
}

/**
 * Eventos tipo 18 que solo piden categoría (como Med Cheer).
 * @return array{nombre:string,categorias:array,valor:int}|null
 */
function eventosTipo18ConfigSoloCategoria(string $idCurso): ?array
{
    if (eventosTipo18EsMedCheer($idCurso)) {
        return eventosTipo18MedCheerConfig();
    }
    if (eventosTipo18EsCopaFlores($idCurso)) {
        return eventosTipo18CopaFloresConfig();
    }
    if (eventosTipo18EsVolleyballFest($idCurso)) {
        return eventosTipo18VolleyballFestConfig();
    }
    return null;
}
