<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/levelup.php';

header('Content-Type: application/json; charset=utf-8');

$cfg = levelupConfig();
jsonResponse([
    'success' => true,
    'tipo_id' => (int) ($cfg['tipo_id'] ?? 4),
    'sedes' => $cfg['sedes'] ?? ['MEDELLÍN', 'RETIRO'],
    'cursos_por_sede_nivel' => $cfg['cursos_por_sede_nivel'] ?? [],
    'modalidades_nivel_1' => $cfg['modalidades_nivel_1'] ?? ['Individual', 'Grupal'],
]);
