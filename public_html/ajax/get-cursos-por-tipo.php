<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$tipoId = (int) ($_GET['tipo_id'] ?? 0);
$configPath = __DIR__ . '/../../config/tipos_inscripcion.php';
$config = file_exists($configPath) ? require $configPath : [];

$cfg = $config[$tipoId] ?? [
    'layout' => 'selector',
    'filterByDate' => true,
    'defaultSede' => 'MEDELLÍN',
];

$filterByDate = (bool) ($cfg['filterByDate'] ?? ($tipoId !== 1));

$curso = new Curso($database);
$rows = $curso->getPorTipo($tipoId, $filterByDate);

$contexto = trim((string) ($_GET['contexto'] ?? ''));
if ($tipoId === 18) {
    require_once __DIR__ . '/../../includes/eventos_tipo18.php';
    if ($contexto === 'equipos') {
        $festIds = eventosTipo18FestivegasIds();
        $rows = array_values(array_filter(
            $rows,
            fn($c) => in_array((string) ($c['ID_Curso'] ?? ''), $festIds, true)
        ));
    } else {
        // Formulario principal: todos los eventos tipo 18 excepto Festivegas
        $principalIds = eventosTipo18PrincipalIds();
        $rows = array_values(array_filter(
            $rows,
            fn($c) => in_array((string) ($c['ID_Curso'] ?? ''), $principalIds, true)
        ));
    }
}

if ($tipoId === 19) {
    require_once __DIR__ . '/../../includes/arquitectos_cerebros.php';
    $allowedIds = arquitectosCerebrosCursoIds();
    $rows = array_values(array_filter(
        $rows,
        fn($c) => in_array((string) ($c['ID_Curso'] ?? ''), $allowedIds, true)
    ));
}

if ($tipoId === 20) {
    require_once __DIR__ . '/../../includes/copa_vegas.php';
    $allowedId = copaVegasCursoId();
    $rows = array_values(array_filter(
        $rows,
        fn($c) => (string) ($c['ID_Curso'] ?? '') === $allowedId
    ));
}

$filterByCupos = !empty($cfg['filterByCupos']);
if ($filterByCupos && !empty($rows)) {
    $anioCupos = (int) date('Y');
    $estadosValidos = ['ACTIVO', 'Confirmado', 'confirmado', 'Incapacitado', 'incapacitado'];
    $rows = array_values(array_filter($rows, function ($c) use ($database, $anioCupos, $estadosValidos) {
        $max = (int) ($c['Cupos_maximos'] ?? 0);
        if ($max <= 0) {
            return true;
        }
        $idCurso = $database->quote((string) ($c['ID_Curso'] ?? ''));
        $estadosList = implode(',', array_map(fn($e) => $database->quote($e), $estadosValidos));
        $row = $database->query(
            "SELECT COUNT(*) AS cnt FROM inscripciones_1 WHERE IDCurso = $idCurso AND año = $anioCupos AND Estado IN ($estadosList)"
        )->fetch();
        $inscritos = (int) ($row['cnt'] ?? 0);
        return $inscritos < $max;
    }));
}

$formatearPrecio = function ($valor) {
    $s = (string) $valor;
    $digits = preg_replace('/[^0-9]/', '', $s);
    if ($digits === '') return '';
    $num = (int) $digits;
    return $num > 0 ? '$' . number_format($num, 0, ',', '.') : '';
};

$formatearFechaHasta = function ($fecha) {
    if (empty($fecha)) return '';
    $d = date_create($fecha);
    return $d ? 'Inscripción abierta hasta ' . $d->format('d/m/Y') : '';
};

$items = array_map(function ($c) use ($formatearPrecio, $formatearFechaHasta) {
    $nombre = $c['Nombre_del_curso'] ?? $c['Nombre_Corto_Curso'] ?? '';
    $precioFormateado = $formatearPrecio($c['Tarifa_Curso'] ?? '');
    $ff = $c['Fecha_Final'] ?? $c['Fecha_Inicio'] ?? '';
    $fechaDisplay = $formatearFechaHasta($ff);
    return [
        'id' => $c['ID_Curso'],
        'nombre' => $nombre,
        'nombre_curso' => $nombre,
        'nombre_display' => $nombre . ($precioFormateado ? ' - ' . $precioFormateado : ''),
        'fecha_inicio' => $c['Fecha_Inicio'] ?? '',
        'fecha_fin' => $c['Fecha_Final'] ?? '',
        'fecha_display' => $fechaDisplay
    ];
}, $rows);

jsonResponse(['success' => true, 'items' => $items, 'config' => $cfg]);
