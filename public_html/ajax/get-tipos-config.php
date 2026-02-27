<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$configPath = __DIR__ . '/../../config/tipos_inscripcion.php';
$config = file_exists($configPath) ? require $configPath : [];
jsonResponse(['success' => true, 'tipos_config' => $config]);
