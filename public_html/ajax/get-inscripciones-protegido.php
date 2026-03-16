<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
}

// Token en header: Authorization: Bearer <API_READ_TOKEN>
requireBearerAuth('API_READ_TOKEN');

$limitRaw = $_GET['limit'] ?? null;
$offsetRaw = $_GET['offset'] ?? 0;
$anioRaw = $_GET['anio'] ?? date('Y');
$limit = null;
$offset = max(0, (int) $offsetRaw);
$anio = (int) $anioRaw;

if ($limitRaw !== null && $limitRaw !== '') {
    $limit = max(1, min(5000, (int) $limitRaw));
}

try {
    $inscripcion = new Inscripcion($database);
    $rows = $inscripcion->getByAnio($anio, $limit, $offset);
    jsonResponse([
        'success' => true,
        'anio' => $anio,
        'count' => count($rows),
        'limit' => $limit,
        'offset' => $offset,
        'items' => $rows
    ]);
} catch (Exception $e) {
    AppLogger::error('get-inscripciones-protegido: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    jsonResponse(['success' => false, 'error' => 'Error consultando inscripciones'], 500);
}
