<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$tipoId = 5;
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

$salidas = array_map(function ($c) use ($formatearPrecio, $formatearFechaHasta) {
    $nombre = $c['Nombre_del_curso'] ?? $c['Nombre_Corto_Curso'] ?? '';
    $precio = $formatearPrecio($c['Tarifa_Curso'] ?? '');
    $f = $c['Fecha_Final'] ?? $c['Fecha_Inicio'] ?? '';
    $fechaDisplay = $formatearFechaHasta($f);
    return [
        'id' => $c['ID_Curso'],
        'nombre' => $nombre . ($precio ? ' - ' . $precio : ''),
        'nombre_solo' => $nombre,
        'nombre_curso' => $nombre,
        'fecha' => $f,
        'fecha_display' => $fechaDisplay
    ];
}, $rows);

jsonResponse(['success' => true, 'salidas' => $salidas]);
