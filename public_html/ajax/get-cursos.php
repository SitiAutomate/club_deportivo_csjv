<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// Variable de entorno SHOW_CUPOS: true para mostrar cupos en el listado (en .env: SHOW_CUPOS=true)
$showCupos = filter_var(env('SHOW_CUPOS', 'false'), FILTER_VALIDATE_BOOLEAN);

$tipoId = (int) ($_GET['tipo_id'] ?? 1);
$lineaId = isset($_GET['linea']) ? (int) $_GET['linea'] : null;
$actividad = isset($_GET['actividad']) ? (int) $_GET['actividad'] : null;
$sede = trim($_GET['sede'] ?? '') ?: null;
$mes = trim($_GET['mes'] ?? '') ?: null;
$anio = (int) ($_GET['anio'] ?? date('Y'));
$participanteDoc = trim($_GET['participante_id'] ?? '');

$meses = [];
if ($mes) {
    $m = (int) $mes;
    $mNext = $m >= 12 ? 1 : $m + 1;
    $meses = [str_pad((string) $m, 2, '0', STR_PAD_LEFT), str_pad((string) $mNext, 2, '0', STR_PAD_LEFT)];
} else {
    $mesActual = (int) date('n');
    $mesSiguiente = $mesActual >= 12 ? 1 : $mesActual + 1;
    $meses = [
        str_pad((string) $mesActual, 2, '0', STR_PAD_LEFT),
        str_pad((string) $mesSiguiente, 2, '0', STR_PAD_LEFT)
    ];
}

$curso = new Curso($database);
$cursos = $curso->getFiltradosConCupos($tipoId, $lineaId, $actividad, $sede, $meses, $anio, $showCupos);

// Excluir cursos donde el participante ya está inscrito (no puede inscribirse 2 veces al año al mismo curso, salvo tipo 3 o 6+ meses)
if ($tipoId === 1 && $participanteDoc !== '' && $mes !== '') {
    $estadosValidos = ['ACTIVO', 'Confirmado', 'confirmado', 'Incapacitado', 'incapacitado'];
    $inscripciones = $database->select('inscripciones_1', ['IDCurso', 'Mes'], [
        'validador_participante' => $participanteDoc,
        'año' => $anio,
        'Estado' => $estadosValidos,
        'Tipo[!]' => 3  // tipo 3 sí permite duplicar
    ]);
    $mesInt = (int) $mes;
    $cursosExcluir = [];
    foreach ($inscripciones as $ins) {
        $idCurso = $ins['IDCurso'] ?? null;
        $mesIns = (int) ($ins['Mes'] ?? 0);
        if (!$idCurso) continue;
        $diff = abs($mesInt - $mesIns);
        if ($diff < 6) {
            $cursosExcluir[$idCurso] = true;
        }
    }
    $cursos = array_values(array_filter($cursos, function ($c) use ($cursosExcluir) {
        return !isset($cursosExcluir[$c['ID_Curso'] ?? $c['id'] ?? '']);
    }));
}

$formatearPrecio = function ($valor) {
    $s = (string) $valor;
    $digits = preg_replace('/[^0-9]/', '', $s);
    if ($digits === '') return '';
    $num = (int) $digits;
    return $num > 0 ? '$' . number_format($num, 0, ',', '.') : '';
};

$cursos = array_map(function ($c) use ($formatearPrecio, $showCupos) {
    $nombre = $c['Nombre_del_curso'] ?? $c['Nombre_Corto_Curso'] ?? '';
    $precioFormateado = $formatearPrecio($c['Tarifa_Curso'] ?? '');
    $c['id'] = $c['ID_Curso'];
    $c['nombre_curso'] = $nombre;
    $c['nombre_solo'] = $nombre;
    $c['nombre'] = $nombre . ($precioFormateado ? ' - ' . $precioFormateado : '');
    if ($showCupos && isset($c['cupos_disponibles'])) {
        $c['nombre'] .= ' (' . $c['cupos_disponibles'] . ' cupos)';
    }
    return $c;
}, $cursos);

jsonResponse(['success' => true, 'cursos' => $cursos]);
