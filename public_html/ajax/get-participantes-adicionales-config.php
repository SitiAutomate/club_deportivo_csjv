<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$tipoId = (int) ($_GET['tipo_id'] ?? 0);
$cursoId = (int) ($_GET['curso_id'] ?? $_GET['campamento_id'] ?? $_GET['item_id'] ?? 0);

if ($tipoId <= 0 || $cursoId <= 0) {
    jsonResponse(['success' => true, 'config' => null]);
}

$configPath = __DIR__ . '/../../config/participantes_adicionales.php';
$configs = file_exists($configPath) ? require $configPath : [];
$key = $tipoId . '_' . $cursoId;
$config = $configs[$key] ?? null;

jsonResponse(['success' => true, 'config' => $config]);
