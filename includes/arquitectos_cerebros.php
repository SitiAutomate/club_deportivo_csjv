<?php

function arquitectosCerebrosConfig(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $path = __DIR__ . '/../config/arquitectos_cerebros.php';
        $cfg = file_exists($path) ? require $path : [];
    }
    return $cfg;
}

function arquitectosCerebrosTipoId(): int
{
    return (int) (arquitectosCerebrosConfig()['tipo_id'] ?? 19);
}

function arquitectosCerebrosCursoIds(): array
{
    $ids = arquitectosCerebrosConfig()['curso_ids'] ?? ['4901', '4902'];
    return array_map('strval', $ids);
}

function arquitectosCerebrosEsCursoValido(string $idCurso): bool
{
    return in_array((string) $idCurso, arquitectosCerebrosCursoIds(), true);
}

function arquitectosCerebrosRoles(): array
{
    return arquitectosCerebrosConfig()['roles'] ?? [];
}

function arquitectosCerebrosModalidadDesdeInput(array $input): string
{
    $rol = trim((string) ($input['ac_rol'] ?? ''));
    $roles = arquitectosCerebrosRoles();
    if (!in_array($rol, $roles, true)) {
        return '';
    }
    if ($rol === 'Otro') {
        $otro = trim((string) ($input['ac_rol_otro'] ?? ''));
        return $otro !== '' ? 'Otro: ' . $otro : '';
    }
    return $rol;
}
