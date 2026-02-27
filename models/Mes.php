<?php

/**
 * Modelo para tabla meses
 * PK: NumMes (varchar)
 */
class Mes
{
    /** @var Medoo\Medoo */
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getAll(): array
    {
        return $this->db->select('meses', ['NumMes', 'Mes', 'Periodo', 'Fecha_Maxima'], ['ORDER' => 'NumMes']);
    }
}
