<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$tipoId = 2;
$curso = new Curso($database);
$rows = $curso->getPorTipo($tipoId, true);

$formatearPrecio = function ($v) {
    $d = preg_replace('/[^0-9]/', '', (string) $v);
    return $d ? '$' . number_format((int) $d, 0, ',', '.') : '';
};

$formatearFechaHasta = function ($fecha) {
    if (empty($fecha)) return '';
    $d = date_create($fecha);
    return $d ? 'Inscripción abierta hasta ' . $d->format('d/m/Y') : '';
};

$campamentos = array_map(function ($c) use ($formatearPrecio, $formatearFechaHasta) {
    $nombre = $c['Nombre_del_curso'] ?? $c['Nombre_Corto_Curso'] ?? '';
    $precio = $formatearPrecio($c['Tarifa_Curso'] ?? '');
    $ff = $c['Fecha_Final'] ?? $c['Fecha_Inicio'] ?? '';
    $fechaDisplay = $formatearFechaHasta($ff);
    return [
        'id' => $c['ID_Curso'],
        'nombre' => $nombre . ($precio ? ' - ' . $precio : ''),
        'nombre_solo' => $nombre,
        'fecha_inicio' => $c['Fecha_Inicio'] ?? '',
        'fecha_fin' => $c['Fecha_Final'] ?? '',
        'fecha_display' => $fechaDisplay
    ];
}, $rows);
jsonResponse(['success' => true, 'campamentos' => $campamentos]);
