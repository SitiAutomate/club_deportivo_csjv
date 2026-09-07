<?php

/**
 * Modelo para tabla cursos_2025
 * PK: ID_Curso (varchar)
 * FK: Actividad -> actividades.IDActividad
 */
class Curso
{
    /** @var Medoo\Medoo */
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Obtener cursos activos
     */
    public function getActivos(): array
    {
        return $this->db->select('cursos_2025', [
            'ID_Curso',
            'Nombre_del_curso',
            'Nombre_Corto_Curso',
            'Descripción',
            'Fecha_Inicio',
            'Fecha_Final',
            'Sede',
            'Linea',
            'Actividad',
            'Tipo',
            'Estado_del_curso',
            'Tarifa_Curso',
            'Cupos_minimos',
            'Cupos_maximos'
        ], [
            'Estado_del_curso' => 'ACTIVO',
            'ORDER' => ['Nombre_del_curso' => 'ASC']
        ]);
    }

    /**
     * Obtener cursos filtrados por estado, tipo (ID), linea (ID), actividad, sede; con cálculo de cupos.
     * Sede solo filtra en frontend, no se usa en el conteo de inscritos para cupos.
     * @param int $tipoId tipos.IDTipo (1=cursos, 2=campamentos, 5=salidas) - numérico
     * @param int|null $lineaId linea.IDLinea - numérico
     * @param int|null $actividad IDActividad
     * @param string|null $sede Sede (solo para filtrar listado, no para conteo cupos)
     * @param array $meses NumMes del mes actual y siguiente ['01','02']
     * @param int $anio
     * @param bool $mostrarCupos Si mostrar cupos disponibles en la respuesta
     */
    public function getFiltradosConCupos(
        int $tipoId,
        ?int $lineaId = null,
        ?int $actividad = null,
        ?string $sede = null,
        array $meses = [],
        int $anio = 0,
        bool $mostrarCupos = false
    ): array {
        $anio = $anio ?: (int) date('Y');

        $where = [
            'Estado_del_curso' => 'ACTIVO',
            'Tipo' => $tipoId,
            'ORDER' => ['Nombre_del_curso' => 'ASC']
        ];
        if ($lineaId !== null && $lineaId > 0) {
            $where['Linea'] = $lineaId;
        }
        if ($actividad !== null && $actividad > 0) {
            $where['Actividad'] = (int) $actividad;
        }
        if ($sede !== null && $sede !== '') {
            $where['Sede'] = (string) $sede;
        }

        $cursos = $this->db->select('cursos_2025', [
            'ID_Curso',
            'Nombre_del_curso',
            'Nombre_Corto_Curso',
            'Tarifa_Curso',
            'Cupos_maximos',
            'Sede',
            'Linea',
            'Actividad',
            'Tipo',
            'Fecha_Inicio',
            'Fecha_Final'
        ], $where);

        if (empty($meses)) {
            $mesActual = (int) date('n');
            $mesSiguiente = $mesActual >= 12 ? 1 : $mesActual + 1;
            $meses = [
                str_pad((string) $mesActual, 2, '0', STR_PAD_LEFT),
                str_pad((string) $mesSiguiente, 2, '0', STR_PAD_LEFT)
            ];
        }

        $estadosValidos = ['ACTIVO', 'Confirmado', 'confirmado', 'Incapacitado', 'incapacitado'];
        $idsConCupo = [];
        foreach ($cursos as $c) {
            if ((int) ($c['Cupos_maximos'] ?? 0) > 0) {
                $idsConCupo[] = (string) ($c['ID_Curso'] ?? '');
            }
        }
        $inscritosPorCurso = $this->contarInscritosPorCurso($idsConCupo, $anio, $estadosValidos, $meses, true);

        $resultado = [];
        foreach ($cursos as $c) {
            $cupoMax = (int) ($c['Cupos_maximos'] ?? 0);
            $inscritos = $cupoMax > 0 ? (int) ($inscritosPorCurso[(string) ($c['ID_Curso'] ?? '')] ?? 0) : 0;
            $disponibles = $cupoMax > 0 ? ($cupoMax - $inscritos) : 999;
            if ($disponibles <= 0) {
                continue;
            }

            $c['id'] = $c['ID_Curso'];
            $c['nombre'] = $c['Nombre_del_curso'] ?? $c['Nombre_Corto_Curso'] ?? '';
            $c['cupos_disponibles'] = $mostrarCupos ? $disponibles : null;
            $c['precio'] = $c['Tarifa_Curso'] ?? '';
            $resultado[] = $c;
        }

        return $resultado;
    }

    /**
     * Una sola consulta agrupada en lugar de un COUNT por cada curso.
     *
     * @param string[] $idsCurso
     * @param string[] $estadosValidos
     * @param string[]|null $meses
     * @return array<string,int> IDCurso => inscritos
     */
    public function contarInscritosPorCurso(
        array $idsCurso,
        int $anio,
        array $estadosValidos,
        ?array $meses = null,
        bool $participantesUnicos = false
    ): array {
        $idsCurso = array_values(array_unique(array_filter(array_map('strval', $idsCurso), fn($id) => $id !== '')));
        if ($idsCurso === [] || $estadosValidos === []) {
            return [];
        }

        $idsList = implode(',', array_map(fn($id) => $this->db->quote($id), $idsCurso));
        $estadosList = implode(',', array_map(fn($e) => $this->db->quote($e), $estadosValidos));
        $anioInt = (int) $anio;
        $cntExpr = $participantesUnicos
            ? 'COUNT(DISTINCT validador_participante)'
            : 'COUNT(*)';
        $sql = "SELECT IDCurso, $cntExpr AS cnt FROM inscripciones_1"
            . " WHERE IDCurso IN ($idsList) AND año = $anioInt AND Estado IN ($estadosList)";
        if ($meses !== null && $meses !== []) {
            $mesList = implode(',', array_map(fn($m) => $this->db->quote((string) $m), $meses));
            $sql .= " AND Mes IN ($mesList)";
        }
        $sql .= ' GROUP BY IDCurso';

        $rows = $this->db->query($sql)->fetchAll();
        $out = [];
        foreach ($rows ?: [] as $row) {
            $out[(string) ($row['IDCurso'] ?? '')] = (int) ($row['cnt'] ?? 0);
        }
        return $out;
    }

    /**
     * Obtener cursos por tipo, opcionalmente filtrados por rango de fechas.
     * Para tipos != 1: solo cursos donde hoy está entre Fecha_Inicio y Fecha_Final.
     */
    public function getPorTipo(
        int $tipoId,
        bool $filterByDate = false
    ): array {
        $where = [
            'Estado_del_curso' => 'ACTIVO',
            'Tipo' => $tipoId,
            'ORDER' => ['Nombre_del_curso' => 'ASC']
        ];
        if ($filterByDate) {
            $hoy = date('Y-m-d');
            $where['Fecha_Inicio[<=]'] = $hoy;
            $where['Fecha_Final[>=]'] = $hoy;
        }
        return $this->db->select('cursos_2025', [
            'ID_Curso',
            'Nombre_del_curso',
            'Nombre_Corto_Curso',
            'Tarifa_Curso',
            'Cupos_maximos',
            'Fecha_Inicio',
            'Fecha_Final'
        ], $where);
    }

    /**
     * Obtener Codigo_Facturacion y Tarifa_Curso por ID de curso (para API inscripción)
     */
    public function getFacturacionPorId(string $idCurso): ?array
    {
        $row = $this->db->get('cursos_2025', [
            'Codigo_Facturacion',
            'Tarifa_Curso'
        ], [
            'ID_Curso' => $idCurso
        ]);
        return $row ?: null;
    }
}
