<?php

function levelupConfig(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $path = __DIR__ . '/../config/levelup.php';
        $cfg = file_exists($path) ? require $path : [];
    }
    return $cfg;
}

function levelupCursoPorSedeYNivel(string $sede, int $nivel): ?array
{
    $cfg = levelupConfig();
    $sede = strtoupper(trim($sede));
    return $cfg['cursos_por_sede_nivel'][$sede][$nivel] ?? null;
}

function levelupEsNivel1(string $idCurso): bool
{
    $cfg = levelupConfig();
    foreach ($cfg['cursos_por_sede_nivel'] ?? [] as $porNivel) {
        foreach ($porNivel as $nivel => $curso) {
            if ((string) ($curso['id'] ?? '') === (string) $idCurso) {
                return (int) $nivel === 1;
            }
        }
    }
    return false;
}
