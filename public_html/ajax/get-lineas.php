<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$linea = new Linea($database);
$lineas = $linea->getAll();
$lineas = array_map(function ($l) {
    $l['id'] = $l['IDLinea'];
    return $l;
}, $lineas);
jsonResponse(['success' => true, 'lineas' => $lineas]);
