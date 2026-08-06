<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/copa_vegas.php';

header('Content-Type: application/json; charset=utf-8');

// Por ahora solo Copa Vegas en el formulario de equipos (Festivegas oculto).
$tipoId = copaVegasTipoId();
$cursoId = copaVegasCursoId();
$curso = new Curso($database);
$rows = array_values(array_filter(
    $curso->getPorTipo($tipoId, true),
    fn($c) => (string) ($c['ID_Curso'] ?? '') === $cursoId
));

$formatearPrecio = function ($valor) {
    $digits = preg_replace('/[^0-9]/', '', (string) $valor);
    if ($digits === '') return '';
    $num = (int) $digits;
    return $num > 0 ? '$' . number_format($num, 0, ',', '.') : '';
};

$items = array_map(function ($c) use ($formatearPrecio) {
    $nombre = $c['Nombre_del_curso'] ?? $c['Nombre_Corto_Curso'] ?? 'Copa Vegas';
    $precio = $formatearPrecio($c['Tarifa_Curso'] ?? '');
    return [
        'id' => $c['ID_Curso'],
        'nombre' => $nombre,
        'nombre_display' => $nombre . ($precio ? ' - ' . $precio : ''),
        'tarifa' => $c['Tarifa_Curso'] ?? '',
        'fecha_inicio' => $c['Fecha_Inicio'] ?? '',
        'fecha_fin' => $c['Fecha_Final'] ?? '',
        'modo' => 'copa_vegas',
        'tipo_id' => (int) ($c['Tipo'] ?? 20),
    ];
}, $rows);

jsonResponse([
    'success' => true,
    'tipo_id' => $tipoId,
    'modo' => 'copa_vegas',
    'items' => $items,
    'default_id' => $cursoId,
]);
