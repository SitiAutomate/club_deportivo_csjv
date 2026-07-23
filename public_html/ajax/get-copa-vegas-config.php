<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/copa_vegas.php';

header('Content-Type: application/json; charset=utf-8');

$cfg = copaVegasConfig();
$disciplinas = [];
foreach (copaVegasDisciplinas() as $nombre => $info) {
    $disciplinas[] = [
        'nombre' => $nombre,
        'precio' => (int) ($info['precio'] ?? 0),
        'precio_display' => copaVegasFormatearPrecio((int) ($info['precio'] ?? 0)),
        'categorias' => $info['categorias'] ?? [],
    ];
}

jsonResponse([
    'success' => true,
    'tipo_id' => copaVegasTipoId(),
    'curso_id' => copaVegasCursoId(),
    'nombre' => $cfg['nombre'] ?? 'Copa Vegas',
    'sedes' => copaVegasSedes(),
    'interno_externo' => $cfg['interno_externo'] ?? ['Interno', 'Externo'],
    'disciplinas' => $disciplinas,
]);
