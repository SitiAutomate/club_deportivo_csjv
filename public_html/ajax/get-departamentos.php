<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$ciudad = new Ciudad($database);
$departamentos = $ciudad->getDepartamentos();
jsonResponse(['success' => true, 'departamentos' => $departamentos]);
