<?php

/**
 * Servicio para sincronizar responsables y participantes con API externa.
 * Usa API_RESPONSABLES, API_PARTICIPANTES y BEARER del .env
 */
class ExternalApiService
{
    private ?string $apiResponsables;
    private ?string $apiParticipantes;
    private ?string $apiInscripcion;
    private ?string $bearer;

    public function __construct()
    {
        $this->apiResponsables = rtrim(env('API_RESPONSABLES', ''), '/');
        $this->apiParticipantes = rtrim(env('API_PARTICIPANTES', ''), '/');
        $this->apiInscripcion = rtrim(env('API_INSCRIPCION', ''), '/');
        $this->bearer = trim(env('BEARER', ''));
    }

    public function isConfigured(): bool
    {
        return $this->bearer !== '' && ($this->apiResponsables !== '' || $this->apiParticipantes !== '' || $this->apiInscripcion !== '');
    }

    /**
     * Enviar inscripción a API externa (por curso individual).
     * POST {API_INSCRIPCION}/{Codigo_Facturacion}/participantes
     * Body: identificacion, periodo (MMYY), valor (float)
     */
    public function crearInscripcionApi(string $codigoFacturacion, string $identificacion, string $periodo, float $valor): bool
    {
        if ($this->apiInscripcion === '' || $this->bearer === '' || $codigoFacturacion === '') {
            return false;
        }
        $url = $this->apiInscripcion . '/' . $codigoFacturacion . '/participantes';
        $body = [
            'identificacion' => $identificacion,
            'periodo' => $periodo,
            'valor' => round($valor, 2),
        ];
        return $this->post($url, $body);
    }

    /**
     * Enviar responsable a API externa.
     * Body esperado: tipo_persona, apellido, tipo_identificacion, identificacion, nombre, ciudad, departamento, direccion, correo, celular
     */
    public function crearResponsable(array $data): bool
    {
        if ($this->apiResponsables === '' || $this->bearer === '') {
            return false;
        }
        $tipoPersona = trim($data['tipo_persona'] ?? '');
        $mapTipo = ['Natural' => 'PN', 'Jurídica' => 'PJ'];
        $tipoPersona = $mapTipo[$tipoPersona] ?? 'PN';

        $body = [
            'tipo_persona' => $tipoPersona,
            'apellido' => mb_strtoupper(trim($data['apellidos'] ?? $data['apellido'] ?? $data['Apellidos'] ?? ''), 'UTF-8'),
            'tipo_identificacion' => trim($data['tipo_identificacion'] ?? '') ?: 'C',
            'identificacion' => trim($data['documento'] ?? $data['identificacion'] ?? $data['IDResponsable'] ?? ''),
            'nombre' => mb_strtoupper(trim($data['nombres'] ?? $data['nombre'] ?? $data['Nombres'] ?? ''), 'UTF-8'),
            'ciudad' => trim($data['ciudad'] ?? '') ?: '',
            'departamento' => trim($data['departamento'] ?? '') ?: '',
            'direccion' => trim($data['direccion'] ?? '') ?: '',
            'correo' => trim($data['email'] ?? $data['correo'] ?? '') ?: '',
            'celular' => trim($data['celular'] ?? $data['telefono'] ?? '') ?: '',
        ];

        return $this->post($this->apiResponsables, $body);
    }

    /**
     * Enviar participante a API externa.
     * Body esperado: identificacion, nombre, responsable (documento del responsable)
     */
    public function crearParticipante(array $data, string $responsableDocumento): bool
    {
        if ($this->apiParticipantes === '' || $this->bearer === '') {
            return false;
        }
        $nombre = trim($data['Nombre_Completo'] ?? $data['nombre'] ?? '');
        if ($nombre === '') {
            $nombre = trim(($data['Primer_Nombre'] ?? '') . ' ' . ($data['Segundo_Nombre'] ?? '') . ' ' . ($data['Primer_Apellido'] ?? '') . ' ' . ($data['Segundo_Apellido'] ?? ''));
        }
        $nombre = mb_strtoupper($nombre, 'UTF-8');

        $body = [
            'identificacion' => trim($data['documento'] ?? $data['IDParticipante'] ?? $data['identificacion'] ?? ''),
            'nombre' => $nombre,
            'responsable' => $responsableDocumento,
        ];

        return $this->post($this->apiParticipantes, $body);
    }

    private function post(string $url, array $body): bool
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->bearer,
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) {
            AppLogger::error('ExternalApiService: ' . $err, ['url' => $url]);
            return false;
        }
        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }
        AppLogger::error('ExternalApiService: HTTP ' . $httpCode, ['url' => $url, 'response' => $response ?: '']);
        return false;
    }
}
