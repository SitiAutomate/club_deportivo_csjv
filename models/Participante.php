<?php

/**
 * Modelo para tabla participantes
 * PK: IDParticipante (varchar)
 * FK: IDResponsable -> responsables.IDResponsable
 */
class Participante
{
    /** @var Medoo\Medoo */
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Buscar participante por documento (IDParticipante)
     * @return array|null Participante con datos del responsable si existe
     */
    public function getByDocumento(string $documento): ?array
    {
        $doc = trim($documento);
        if ($doc === '') return null;

        $participante = $this->db->get('participantes', [
            '[>]responsables' => ['IDResponsable' => 'IDResponsable']
        ], [
            'participantes.IDParticipante',
            'participantes.Verificador',
            'participantes.Tipo_documento',
            'participantes.IDResponsable',
            'participantes.Primer_Nombre',
            'participantes.Segundo_Nombre',
            'participantes.Primer_Apellido',
            'participantes.Segundo_Apellido',
            'participantes.Nombre_Completo',
            'participantes.Fecha_Nacimiento',
            'participantes.interno_externo',
            'participantes.Grupo',
            'responsables.IDResponsable(responsable_id_real)',
            'responsables.Nombres(responsable_nombres)',
            'responsables.Apellidos(responsable_apellidos)',
            'responsables.Nombre_Completo(responsable_nombre_completo)'
        ], [
            'participantes.IDParticipante' => $doc
        ]);

        return $participante ?: null;
    }

    /**
     * Crear participante
     */
    public function create(array $data): string
    {
        $doc = trim($data['documento'] ?? '');
        $nombreCompleto = trim(($data['Primer_Nombre'] ?? '') . ' ' . ($data['Segundo_Nombre'] ?? '')) . ' '
            . trim(($data['Primer_Apellido'] ?? '') . ' ' . ($data['Segundo_Apellido'] ?? ''));

        $this->db->insert('participantes', [
            'IDParticipante' => $doc,
            'Verificador' => $data['Verificador'] ?? null,
            'Tipo_documento' => $data['Tipo_documento'] ?? null,
            'IDResponsable' => $data['IDResponsable'] ?? null,
            'Primer_Nombre' => trim($data['Primer_Nombre'] ?? $data['nombre'] ?? ''),
            'Segundo_Nombre' => trim($data['Segundo_Nombre'] ?? ''),
            'Primer_Apellido' => trim($data['Primer_Apellido'] ?? $data['apellido'] ?? ''),
            'Segundo_Apellido' => trim($data['Segundo_Apellido'] ?? ''),
            'Nombre_Completo' => $nombreCompleto ?: null,
            'Fecha_Nacimiento' => !empty($data['Fecha_Nacimiento'] ?? $data['fecha_nacimiento']) ? ($data['Fecha_Nacimiento'] ?? $data['fecha_nacimiento']) : null,
            'interno_externo' => $data['interno_externo'] ?? null,
            'Grupo' => $data['Grupo'] ?? null
        ]);
        return $doc;
    }

    /**
     * Actualizar responsable asignado al participante
     */
    public function updateResponsable(string $participanteDocumento, string $responsableDocumento): bool
    {
        $result = $this->db->update('participantes', [
            'IDResponsable' => $responsableDocumento
        ], [
            'IDParticipante' => $participanteDocumento
        ]);
        return $result->rowCount() > 0;
    }

    /**
     * Verificar si el documento del responsable coincide con el asignado al participante
     */
    public function isResponsableAsignado(string $participanteDocumento, string $responsableDocumento): bool
    {
        $row = $this->db->get('participantes', [
            '[>]responsables' => ['IDResponsable' => 'IDResponsable']
        ], [
            'responsables.IDResponsable(responsable_documento)'
        ], [
            'participantes.IDParticipante' => $participanteDocumento
        ]);

        return $row && trim($responsableDocumento) === trim($row['responsable_documento'] ?? '');
    }
}
