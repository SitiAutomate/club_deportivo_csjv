<?php
/**
 * Devuelve el HTML del detalle de un item (salida, curso tipo 14, etc.) por tipo e ID.
 * Plantillas: templates/{tipo_id}/{item_id}.html
 * Ej: templates/5/5112.html (salida), templates/14/123.html (tipo 14)
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$tipoId = (int) ($_GET['tipo_id'] ?? 0);
$itemId = (int) ($_GET['item_id'] ?? $_GET['id'] ?? 0);

if ($tipoId <= 0 || $itemId <= 0) {
    echo json_encode(['success' => true, 'html' => '']);
    exit;
}

$templatesDir = dirname(__DIR__, 2) . '/templates';
$file = $templatesDir . '/' . $tipoId . '/' . $itemId . '.html';

$html = '';
if (file_exists($file) && is_readable($file)) {
    $html = file_get_contents($file);
}

echo json_encode(['success' => true, 'html' => $html]);
