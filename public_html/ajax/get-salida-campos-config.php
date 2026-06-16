<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/salidas_campos.php';

header('Content-Type: application/json; charset=utf-8');

$salidaId = trim((string) ($_GET['salida_id'] ?? $_GET['id'] ?? ''));
$config = salidasCamposPorCurso($salidaId);

jsonResponse([
    'success' => true,
    'salida_id' => $salidaId,
    'config' => $config,
]);
