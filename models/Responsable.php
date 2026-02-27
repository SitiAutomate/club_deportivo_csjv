<?php

/**
 * Modelo para tabla responsables
 * PK: IDResponsable (varchar - documento)
 */
class Responsable
{
    /** @var Medoo\Medoo */
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Buscar responsable por documento (IDResponsable)
     */
    public function getByDocumento(string $documento): ?array
    {
        $doc = trim($documento);
        if ($doc === '') return null;

        $row = $this->db->get('responsables', '*', ['IDResponsable' => $doc]);
        return $row ?: null;
    }

    /**
     * Crear responsable
     * @return string IDResponsable (documento)
     */
    public function create(array $data): string
    {
        $doc = trim($data['documento'] ?? $data['IDResponsable'] ?? '');
        $nombres = trim($data['Nombres'] ?? $data['nombre'] ?? '');
        $apellidos = trim($data['Apellidos'] ?? $data['apellido'] ?? '');
        $nombreCompleto = trim($nombres . ' ' . $apellidos) ?: null;

        $this->db->insert('responsables', [
            'IDResponsable' => $doc,
            'Verificador_id' => $data['Verificador_id'] ?? null,
            'Nombres' => $nombres ?: null,
            'Apellidos' => $apellidos ?: null,
            'Nombre_Completo' => $nombreCompleto,
            'Correo_Responsable' => trim($data['Correo_Responsable'] ?? $data['email'] ?? '') ?: null,
            'Verificador_correo' => $data['Verificador_correo'] ?? null,
            'Celular_Responsable' => trim($data['Celular_Responsable'] ?? $data['telefono'] ?? '') ?: null,
            'Tipo_Persona' => $data['Tipo_Persona'] ?? null,
            'Ciudad' => $data['Ciudad'] ?? null,
            'direccion' => trim($data['direccion'] ?? '') ?: null,
            'tipo_identificacion' => $data['tipo_identificacion'] ?? null
        ]);
        return $doc;
    }
}
