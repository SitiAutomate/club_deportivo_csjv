<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$mes = new Mes($database);
$todos = $mes->getAll();

$mesActual = (int) date('n');
$mesSiguiente = $mesActual >= 12 ? 1 : $mesActual + 1;
$numActual = str_pad((string) $mesActual, 2, '0', STR_PAD_LEFT);
$numSiguiente = str_pad((string) $mesSiguiente, 2, '0', STR_PAD_LEFT);

$opciones = [];
foreach ($todos as $m) {
    $num = (string) $m['NumMes'];
    if ($num === $numActual || $num === $numSiguiente) {
        $opciones[] = [
            'id' => $num,
            'NumMes' => $num,
            'Mes' => $m['Mes'] ?? '',
            'Periodo' => $m['Periodo'] ?? ''
        ];
    }
}

if (empty($opciones)) {
    $opciones = [
        ['id' => $numActual, 'NumMes' => $numActual, 'Mes' => date('F', mktime(0, 0, 0, $mesActual, 1)), 'Periodo' => ''],
        ['id' => $numSiguiente, 'NumMes' => $numSiguiente, 'Mes' => date('F', mktime(0, 0, 0, $mesSiguiente, 1)), 'Periodo' => '']
    ];
}

jsonResponse(['success' => true, 'meses' => $opciones]);
