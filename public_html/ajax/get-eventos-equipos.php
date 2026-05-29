<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/eventos_tipo18.php';

header('Content-Type: application/json; charset=utf-8');

$tipoId = 18;
$festivegasIds = eventosTipo18FestivegasIds();
$curso = new Curso($database);
$rows = array_values(array_filter(
    $curso->getPorTipo($tipoId, false),
    fn($c) => in_array((string) ($c['ID_Curso'] ?? ''), $festivegasIds, true)
));

$formatearPrecio = function ($valor) {
    $digits = preg_replace('/[^0-9]/', '', (string) $valor);
    if ($digits === '') return '';
    $num = (int) $digits;
    return $num > 0 ? '$' . number_format($num, 0, ',', '.') : '';
};

$items = array_map(function ($c) use ($formatearPrecio) {
    $nombre = $c['Nombre_del_curso'] ?? $c['Nombre_Corto_Curso'] ?? '';
    $precio = $formatearPrecio($c['Tarifa_Curso'] ?? '');
    return [
        'id' => $c['ID_Curso'],
        'nombre' => $nombre,
        'nombre_display' => $nombre . ($precio ? ' - ' . $precio . ' por equipo' : ''),
        'tarifa' => $c['Tarifa_Curso'] ?? '',
        'fecha_inicio' => $c['Fecha_Inicio'] ?? '',
        'fecha_fin' => $c['Fecha_Final'] ?? '',
    ];
}, $rows);

jsonResponse([
    'success' => true,
    'tipo_id' => $tipoId,
    'items' => $items,
    'default_id' => $festivegasIds[0] ?? '1801',
]);
