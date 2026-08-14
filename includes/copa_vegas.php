<?php

function copaVegasConfig(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $path = __DIR__ . '/../config/copa_vegas.php';
        $cfg = file_exists($path) ? require $path : [];
    }
    return $cfg;
}

function copaVegasTipoId(): int
{
    return (int) (copaVegasConfig()['tipo_id'] ?? 20);
}

function copaVegasCursoId(): string
{
    $id = trim((string) (copaVegasConfig()['curso_id'] ?? '2001'));
    return $id !== '' ? $id : '2001';
}

function copaVegasDisciplinas(): array
{
    return copaVegasConfig()['disciplinas'] ?? [];
}

function copaVegasSedes(): array
{
    return copaVegasConfig()['sedes'] ?? ['MEDELLÍN', 'RETIRO'];
}

function copaVegasEsCursoValido(string $idCurso): bool
{
    return (string) $idCurso === copaVegasCursoId();
}

function copaVegasDisciplinaValida(string $disciplina): bool
{
    return array_key_exists($disciplina, copaVegasDisciplinas());
}

function copaVegasCategoriaValida(string $disciplina, string $categoria): bool
{
    $disc = copaVegasDisciplinas()[$disciplina] ?? null;
    if (!$disc) {
        return false;
    }
    $cats = $disc['categorias'] ?? [];
    return in_array($categoria, $cats, true);
}

function copaVegasPrecio(string $disciplina): int
{
    $disc = copaVegasDisciplinas()[$disciplina] ?? [];
    return (int) ($disc['precio'] ?? 0);
}

function copaVegasFormatearPrecio(int $valor): string
{
    return '$' . number_format($valor, 0, ',', '.');
}

function copaVegasDisciplinasEquipo(): array
{
    $ids = copaVegasConfig()['disciplinas_equipo'] ?? ['Fútbol', 'Baloncesto', 'Voleibol'];
    return array_values(array_filter(array_map('strval', $ids)));
}

function copaVegasEsDisciplinaEquipo(string $disciplina): bool
{
    return in_array($disciplina, copaVegasDisciplinasEquipo(), true);
}

function copaVegasOpcionesProcedencia(): array
{
    return copaVegasConfig()['interno_externo'] ?? ['San José de Las Vegas', 'Externo'];
}

function copaVegasFormatearFechaLimite(?string $fecha): string
{
    if ($fecha === null || trim($fecha) === '') {
        return '';
    }
    $d = date_create($fecha);
    if (!$d) {
        return '';
    }
    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];
    $mes = $meses[(int) $d->format('n')] ?? $d->format('m');
    return (int) $d->format('j') . ' de ' . $mes . ' de ' . $d->format('Y');
}
