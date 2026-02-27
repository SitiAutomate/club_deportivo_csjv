<?php
/**
 * Devuelve el HTML del detalle de una salida específica.
 * Las plantillas se almacenan en templates/salidas/{salida_id}.html
 * Si existe el archivo, devuelve su contenido; si no, devuelve vacío.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$salidaId = (int) ($_GET['salida_id'] ?? $_GET['id'] ?? 0);
if ($salidaId <= 0) {
    echo json_encode(['success' => true, 'html' => '']);
    exit;
}

$templatesDir = dirname(__DIR__, 2) . '/templates/salidas';
$file = $templatesDir . '/' . $salidaId . '.html';

$html = '';
if (file_exists($file) && is_readable($file)) {
    $html = file_get_contents($file);
}

echo json_encode(['success' => true, 'html' => $html]);
