<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
}
csrfValidate();

header('Content-Type: application/json; charset=utf-8');

$input = getPostData();
$participanteDocumento = trim($input['participante_id'] ?? $input['participante_documento'] ?? '');
$responsableDocumento = trim($input['responsable_id'] ?? $input['responsable_documento'] ?? '');

if ($participanteDocumento === '' || $responsableDocumento === '') {
    jsonResponse(['success' => false, 'error' => 'participante_id y responsable_id (documentos) son requeridos'], 400);
}

$participante = new Participante($database);

try {
    $ok = $participante->updateResponsable($participanteDocumento, $responsableDocumento);
    if ($ok) {
        jsonResponse(['success' => true]);
    } else {
        jsonResponse(['success' => false, 'error' => 'No se pudo actualizar'], 500);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
