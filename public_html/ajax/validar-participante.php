<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfValidate();
}

$input = getPostData();
$documento = trim($input['documento'] ?? '');

if ($documento === '') {
    jsonResponse(['success' => false, 'error' => 'Documento requerido'], 400);
}

$participante = new Participante($database);
$row = $participante->getByDocumento($documento);

if ($row) {
    $responsableDoc = $row['IDResponsable'] ?? $row['responsable_id_real'] ?? null;
    $responsableNombre = trim(($row['responsable_nombres'] ?? '') . ' ' . ($row['responsable_apellidos'] ?? ''))
        ?: ($row['responsable_nombre_completo'] ?? null);

    jsonResponse([
        'success' => true,
        'exists' => true,
        'participante' => [
            'id' => $row['IDParticipante'],
            'documento' => $row['IDParticipante'],
            'nombre' => $row['Nombre_Completo'] ?? trim(($row['Primer_Nombre'] ?? '') . ' ' . ($row['Primer_Apellido'] ?? '')),
            'apellido' => $row['Primer_Apellido'] ?? '',
            'fecha_nacimiento' => $row['Fecha_Nacimiento'] ?? null,
            'responsable_id' => $responsableDoc,
            'responsable_documento' => $responsableDoc,
            'responsable_nombre' => $responsableNombre
        ]
    ]);
} else {
    jsonResponse([
        'success' => true,
        'exists' => false
    ]);
}
