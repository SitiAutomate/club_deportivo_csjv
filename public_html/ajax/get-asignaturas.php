<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$asignaturaModel = new Asignatura($database);
$rows = $asignaturaModel->getAll();

$items = array_map(function ($r) {
    return [
        'id' => (int) ($r['IDAsignatura'] ?? 0),
        'nombre' => $r['Asignatura'] ?? '',
    ];
}, $rows);

jsonResponse(['success' => true, 'items' => $items]);
