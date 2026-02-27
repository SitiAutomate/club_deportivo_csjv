<?php

/**
 * Modelo para tabla linea
 */
class Linea
{
    /** @var Medoo\Medoo */
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getAll(): array
    {
        return $this->db->select('linea', ['IDLinea', 'Nombre_Linea'], ['ORDER' => 'Nombre_Linea']);
    }
}
