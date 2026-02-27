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
        return $this->db->select('actividades', ['IDActividad', 'Nombre_Actividad', 'CC', 'ESTADO'], [
            'ESTADO' => 'ACTIVO',
            'ORDER' => 'Nombre_Actividad'
        ]);
    }
}
