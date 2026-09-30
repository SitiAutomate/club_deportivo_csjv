<?php

/**
 * Servicio para sincronizar responsables, participantes e inscripciones con API externa.
 *
 * Auth: POST API_LOGIN con { usuario, clave, empresa } → accessToken (reemplaza BEARER estático).
 * Empresa según línea del curso: línea 1 → "03", línea 2 → "04".
 */
class ExternalApiService
{
    private ?string $apiLogin;
    private ?string $apiUser;
    private ?string $apiPassword;
    private ?string $apiResponsables;
    private ?string $apiParticipantes;
    private ?string $apiInscripcion;
    /** @var array<string, string> accessToken por empresa en esta petición */
    private array $tokenCache = [];

    public function __construct()
    {
        $this->apiLogin = rtrim(env('API_LOGIN', ''), '/');
        $this->apiUser = trim(env('API_USER', ''));
        $this->apiPassword = (string) env('API_PASSWORD', '');
        $this->apiResponsables = rtrim(env('API_RESPONSABLES', ''), '/');
        $this->apiParticipantes = rtrim(env('API_PARTICIPANTES', ''), '/');
        $this->apiInscripcion = rtrim(env('API_INSCRIPCION', ''), '/');
    }

    public function isConfigured(): bool
    {
        if ($this->apiLogin === '' || $this->apiUser === '' || $this->apiPassword === '') {
            return false;
        }
        return $this->apiResponsables !== '' || $this->apiParticipantes !== '' || $this->apiInscripcion !== '';
    }

    /**
     * Línea del curso → código empresa API.
     * Línea 1 → 03, línea 2 → 04.
     */
    public static function empresaDesdeLinea(?int $lineaId): ?string
    {
        if ($lineaId === 1) {
            return '03';
        }
        if ($lineaId === 2) {
            return '04';
        }
        return null;
    }

    /**
     * Empresas a sincronizar cuando aún no hay línea (alta de persona).
     *
     * @return string[]
     */
    public static function empresasPorDefecto(): array
    {
        return ['03', '04'];
    }

    /**
     * POST API_LOGIN → accessToken.
     */
    public function login(string $empresa): ?string
    {
        if ($this->apiLogin === '' || $this->apiUser === '' || $this->apiPassword === '') {
            return null;
        }

        $body = [
            'usuario' => $this->apiUser,
            'clave' => $this->apiPassword,
            'empresa' => $empresa,
        ];

        $ch = curl_init($this->apiLogin);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            AppLogger::error('ExternalApiService login: ' . $err, ['empresa' => $empresa]);
            return null;
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            AppLogger::error('ExternalApiService login: HTTP ' . $httpCode, [
                'empresa' => $empresa,
                'response' => $response ?: '',
            ]);
            return null;
        }

        $data = json_decode((string) $response, true);
        if (!is_array($data)) {
            AppLogger::error('ExternalApiService login: respuesta no JSON', ['empresa' => $empresa]);
            return null;
        }

        $token = trim((string) (
            $data['accessToken']
            ?? $data['access_token']
            ?? ($data['data']['accessToken'] ?? null)
            ?? ($data['data']['access_token'] ?? null)
            ?? ''
        ));
        if ($token === '') {
            AppLogger::error('ExternalApiService login: sin accessToken', ['empresa' => $empresa]);
            return null;
        }

        $this->tokenCache[$empresa] = $token;
        return $token;
    }

    /**
     * Obtiene (o renueva en esta petición) el accessToken para una empresa.
     */
    public function ensureAccessToken(string $empresa): ?string
    {
        if (isset($this->tokenCache[$empresa]) && $this->tokenCache[$empresa] !== '') {
            return $this->tokenCache[$empresa];
        }
        return $this->login($empresa);
    }

    /**
     * Enviar inscripción a API externa (por curso individual).
     * POST {API_INSCRIPCION} con Bearer accessToken del login.
     * Body: identificacion, grupo (Codigo_Facturacion), anio, mes (MM), valor
     */
    public function crearInscripcionApi(
        string $codigoFacturacion,
        string $identificacion,
        string $anio,
        string $mes,
        $valor,
        ?string $empresa = null
    ): bool {
        if ($this->apiInscripcion === '' || $codigoFacturacion === '') {
            return false;
        }
        if ($empresa === null || $empresa === '') {
            AppLogger::error('ExternalApiService crearInscripcionApi: empresa requerida');
            return false;
        }
        $mesNorm = str_pad(preg_replace('/\D/', '', $mes) ?: '', 2, '0', STR_PAD_LEFT);
        if (strlen($mesNorm) > 2) {
            $mesNorm = substr($mesNorm, -2);
        }
        $body = [
            'identificacion' => $identificacion,
            'grupo' => $codigoFacturacion,
            'anio' => (string) $anio,
            'mes' => $mesNorm,
            'valor' => is_numeric($valor) ? (string) round((float) $valor, 2) : (string) $valor,
        ];
        return $this->post($this->apiInscripcion, $body, $empresa);
    }

    /**
     * Enviar responsable (cliente) a API externa.
     * Body esperado: identificacion, tipo_persona, tipo_identificacion, nombre, apellido,
     * ciudad, direccion, correo, celular, departamento
     */
    public function crearResponsable(array $data, ?string $empresa = null): bool
    {
        if ($this->apiResponsables === '') {
            return false;
        }
        $tipoPersona = trim((string) ($data['tipo_persona'] ?? ''));
        $mapTipo = ['Natural' => 'PN', 'Jurídica' => 'PJ', 'PN' => 'PN', 'PJ' => 'PJ'];
        $tipoPersona = $mapTipo[$tipoPersona] ?? 'PN';

        $ciudad = trim((string) ($data['ciudad'] ?? $data['Ciudad'] ?? ''));
        $departamento = trim((string) ($data['departamento'] ?? ''));
        if ($departamento === '' && preg_match('/^\d{5}$/', $ciudad)) {
            $departamento = substr($ciudad, 0, 2);
        }

        $body = [
            'identificacion' => trim((string) ($data['documento'] ?? $data['identificacion'] ?? $data['IDResponsable'] ?? '')),
            'tipo_persona' => $tipoPersona,
            'tipo_identificacion' => self::mapTipoIdentificacionApi(
                (string) ($data['tipo_identificacion'] ?? ''),
                'CC'
            ),
            'nombre' => mb_strtoupper(trim((string) ($data['nombres'] ?? $data['nombre'] ?? $data['Nombres'] ?? '')), 'UTF-8'),
            'apellido' => mb_strtoupper(trim((string) ($data['apellidos'] ?? $data['apellido'] ?? $data['Apellidos'] ?? '')), 'UTF-8'),
            'ciudad' => $ciudad,
            'direccion' => trim((string) ($data['direccion'] ?? '')),
            'correo' => trim((string) ($data['email'] ?? $data['correo'] ?? $data['Correo_Responsable'] ?? '')),
            'celular' => trim((string) ($data['celular'] ?? $data['telefono'] ?? $data['Celular_Responsable'] ?? '')),
            'departamento' => $departamento,
        ];

        if ($empresa !== null && $empresa !== '') {
            return $this->post($this->apiResponsables, $body, $empresa);
        }

        $ok = false;
        foreach (self::empresasPorDefecto() as $emp) {
            if ($this->post($this->apiResponsables, $body, $emp)) {
                $ok = true;
            }
        }
        return $ok;
    }

    /**
     * Enviar participante (usuario) a API externa.
     * Body esperado: identificacion, tipo_identificacion, nombre, apellido, fecha_nacimiento, responsable
     */
    public function crearParticipante(array $data, string $responsableDocumento, ?string $empresa = null): bool
    {
        if ($this->apiParticipantes === '') {
            return false;
        }

        $nombre = trim((string) ($data['nombre'] ?? ''));
        if ($nombre === '') {
            $nombre = trim(
                trim((string) ($data['Primer_Nombre'] ?? '')) . ' ' .
                trim((string) ($data['Segundo_Nombre'] ?? ''))
            );
        }

        $apellido = trim((string) ($data['apellido'] ?? $data['apellidos'] ?? ''));
        if ($apellido === '') {
            $apellido = trim(
                trim((string) ($data['Primer_Apellido'] ?? '')) . ' ' .
                trim((string) ($data['Segundo_Apellido'] ?? ''))
            );
        }

        $fecha = trim((string) (
            $data['fecha_nacimiento']
            ?? $data['Fecha_Nacimiento']
            ?? ''
        ));
        if ($fecha !== '' && preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $fecha, $m)) {
            $fecha = $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        if ($fecha !== '' && strpos($fecha, ' ') !== false) {
            $fecha = substr($fecha, 0, 10);
        }

        $tipoDoc = (string) (
            $data['tipo_identificacion']
            ?? $data['Tipo_documento']
            ?? ''
        );

        $body = [
            'identificacion' => trim((string) ($data['documento'] ?? $data['IDParticipante'] ?? $data['identificacion'] ?? '')),
            'tipo_identificacion' => self::mapTipoIdentificacionApi($tipoDoc, 'TI'),
            'nombre' => mb_strtoupper($nombre, 'UTF-8'),
            'apellido' => mb_strtoupper($apellido, 'UTF-8'),
            'fecha_nacimiento' => $fecha,
            'responsable' => $responsableDocumento,
        ];

        if ($empresa !== null && $empresa !== '') {
            return $this->post($this->apiParticipantes, $body, $empresa);
        }

        $ok = false;
        foreach (self::empresasPorDefecto() as $emp) {
            if ($this->post($this->apiParticipantes, $body, $emp)) {
                $ok = true;
            }
        }
        return $ok;
    }

    /**
     * Códigos locales del formulario → códigos API (CC, TI, etc.).
     */
    public static function mapTipoIdentificacionApi(string $tipo, string $default = 'CC'): string
    {
        $t = strtoupper(trim($tipo));
        if ($t === '') {
            return $default;
        }
        $map = [
            'C' => 'CC',
            'CC' => 'CC',
            'T' => 'TI',
            'TI' => 'TI',
            'U' => 'RC',
            'RC' => 'RC',
            'E' => 'CE',
            'CE' => 'CE',
            'X' => 'TE',
            'TE' => 'TE',
            'O' => 'PA',
            'PA' => 'PA',
            'PP' => 'PA',
            'N' => 'NIT',
            'NIT' => 'NIT',
            'Y' => 'CE',
        ];
        return $map[$t] ?? $default;
    }

    private function post(string $url, array $body, string $empresa): bool
    {
        $token = $this->ensureAccessToken($empresa);
        if ($token === null || $token === '') {
            AppLogger::error('ExternalApiService: sin accessToken', ['url' => $url, 'empresa' => $empresa]);
            return false;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) {
            AppLogger::error('ExternalApiService: ' . $err, ['url' => $url, 'empresa' => $empresa]);
            return false;
        }
        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }
        // Token vencido en mitad de la petición: un reintento con login fresco
        if (in_array($httpCode, [401, 403], true)) {
            unset($this->tokenCache[$empresa]);
            $token = $this->login($empresa);
            if ($token) {
                return $this->postOnce($url, $body, $token, $empresa);
            }
        }
        AppLogger::error('ExternalApiService: HTTP ' . $httpCode, [
            'url' => $url,
            'empresa' => $empresa,
            'response' => $response ?: '',
        ]);
        return false;
    }

    private function postOnce(string $url, array $body, string $token, string $empresa): bool
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err || $httpCode < 200 || $httpCode >= 300) {
            AppLogger::error('ExternalApiService retry: HTTP ' . $httpCode, [
                'url' => $url,
                'empresa' => $empresa,
                'err' => $err,
                'response' => $response ?: '',
            ]);
            return false;
        }
        return true;
    }
}
