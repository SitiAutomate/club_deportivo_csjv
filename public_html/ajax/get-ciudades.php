<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$depto = trim($_GET['depto'] ?? '');
if ($depto === '') {
    jsonResponse(['success' => true, 'ciudades' => []]);
    exit;
}

$ciudad = new Ciudad($database);
$ciudades = $ciudad->getCiudadesByDepto($depto);
jsonResponse(['success' => true, 'ciudades' => $ciudades]);
