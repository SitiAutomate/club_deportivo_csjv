<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$configPath = __DIR__ . '/../../config/datos_adicionales.php';
$config = file_exists($configPath) ? require $configPath : [];
$campos = array_filter($config, fn($c) => !empty($c['enabled']));
jsonResponse(['success' => true, 'campos' => $campos]);
