<?php

/**
 * Modelo para tabla asignaturas
 * PK: IDAsignatura (int)
 */
class Asignatura
{
    /** @var Medoo\Medoo */
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getAll(): array
    {
        return $this->db->select('asignaturas', ['IDAsignatura', 'Asignatura'], ['ORDER' => 'Asignatura']);
    }
}
