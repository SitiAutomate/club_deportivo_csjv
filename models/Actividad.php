<?php

/**
 * Modelo para tabla actividades
 * PK: IDActividad (int)
 */
class Actividad
{
    /** @var Medoo\Medoo */
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getActivas(): array
    {
        return $this->db->select('actividades', ['IDActividad', 'Nombre_Actividad', 'CC', 'ESTADO', 'IDNegocio'], [
            'ESTADO' => 'ACTIVO',
            'ORDER' => 'Nombre_Actividad'
        ]) ?: [];
    }

    /**
     * Actividades activas asociadas a cursos tipo 1, opcionalmente filtradas por línea (IDNegocio).
     */
    public function getActivasParaCursosTipo1(?int $lineaId = null): array
    {
        $actividadIds = $this->db->select('cursos_2025', 'Actividad', [
            'Estado_del_curso' => 'ACTIVO',
            'Tipo' => 1
        ]);
        $actividadIds = array_unique(array_filter(array_map('intval', $actividadIds ?: [])));
        if (empty($actividadIds)) {
            return [];
        }

        $where = [
            'IDActividad' => $actividadIds,
            'ESTADO' => 'ACTIVO',
            'ORDER' => 'Nombre_Actividad'
        ];
        if ($lineaId !== null && $lineaId > 0) {
            $where['IDNegocio'] = $lineaId;
        }

        return $this->db->select('actividades', ['IDActividad', 'Nombre_Actividad', 'CC', 'ESTADO', 'IDNegocio'], $where) ?: [];
    }
}
