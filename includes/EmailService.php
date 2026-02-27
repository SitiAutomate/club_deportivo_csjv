<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Servicio de envío de emails usando PHPMailer
 */
class EmailService
{
    private string $host;
    private int $port;
    private string $user;
    private string $pass;
    private string $fromName;
    private string $baseUrl;

    public function __construct()
    {
        $this->host = env('EMAIL_HOST', '');
        $this->port = (int) env('EMAIL_PORT', 587);
        $this->user = env('EMAIL_USER', '');
        $this->pass = env('EMAIL_PASS', '');
        $this->fromName = 'Club Deportivo y Maex';
        $this->baseUrl = rtrim(env('APP_URL', ''), '/');
        if ($this->baseUrl === '' && isset($_SERVER['HTTP_HOST'])) {
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $this->baseUrl = $proto . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME'], 2);
        }
    }

    public function isConfigured(): bool
    {
        return $this->host !== '' && $this->user !== '' && $this->pass !== '';
    }

    /**
     * Enviar email de confirmación de inscripción
     */
    public function enviarConfirmacionInscripcion(
        string $destinatario,
        string $participanteNombre,
        string $responsableNombre,
        string $tipoTexto,
        string $detalleTexto,
        ?string $transporte = null
    ): bool {
        if (!$this->isConfigured() || $destinatario === '') {
            return false;
        }

        $logoHtml = $this->getLogoEmbedHtml();
        $transporteHtml = ($transporte && $transporte === 'Sí')
            ? '<p><strong>Transporte:</strong> Sí</p>'
            : '';

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background:#f5f5f5;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:24px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
<tr>
    <td style="background:linear-gradient(180deg,#f8fafc 0%,#f1f5f9 100%);padding:24px;text-align:center;border-bottom:1px solid #e2e8f0;">
        {$logoHtml}
        <h1 style="margin:16px 0 0;font-size:1.5rem;color:#1e293b;">Club Deportivo y Maex</h1>
    </td>
</tr>
<tr>
    <td style="padding:24px;">
        <h2 style="margin:0 0 20px;font-size:1.25rem;color:#20254A;">✓ Inscripción registrada correctamente</h2>
        <p style="margin:0 0 20px;color:#334155;line-height:1.6;">Hemos recibido su inscripción. A continuación el detalle:</p>

        <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
        <tr><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;"><strong>Participante:</strong></td><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;">{$this->esc($participanteNombre)}</td></tr>
        <tr><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;"><strong>Responsable: </strong></td><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;">{$this->esc($responsableNombre)}</td></tr>
        <tr><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;"><strong>{$this->esc($tipoTexto)}:</strong></td><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;">{$this->formatDetalleEmail($detalleTexto)}</td></tr>
        </table>
        {$transporteHtml}
        <p style="margin:20px 0 0;font-size:0.9rem;color:#64748b;">Si tiene alguna duda, contáctenos a <a href="mailto:clubdeportivo@sanjosevegas.edu.co">clubdeportivo@sanjosevegas.edu.co</a></p>
    </td>
</tr>
<tr>
    <td style="padding:16px;background:#f8fafc;text-align:center;font-size:0.85rem;color:#64748b;">
        Club Deportivo y Maex · San José de Las Vegas
    </td>
</tr>
</table>
</td></tr></table>
</body>
</html>
HTML;

        return $this->enviar($destinatario, 'Confirmación de inscripción - Club Deportivo y Maex', $html);
    }

    private function getLogoEmbedHtml(): string
    {
        $paths = [
            __DIR__ . '/../public_html/assets/images/logo.png',
            (isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' : '') . 'assets/images/logo.png',
        ];
        foreach ($paths as $path) {
            if (!empty($path) && file_exists($path) && is_readable($path)) {
                $data = file_get_contents($path);
                $mime = 'image/png';
                if (function_exists('mime_content_type')) {
                    $mime = mime_content_type($path) ?: $mime;
                }
                $b64 = base64_encode($data);
                return '<img src="data:' . $mime . ';base64,' . $b64 . '" alt="Club Deportivo y Maex" style="max-height:70px;">';
            }
        }
        return '';
    }

    private function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    /** Formatea el detalle: si hay varios cursos separados por coma, los muestra con línea separadora */
    private function formatDetalleEmail(string $detalleTexto): string
    {
        $partes = preg_split('/,\s*/', $detalleTexto);
        if (count($partes) <= 1) {
            return $this->esc($detalleTexto);
        }
        $seguros = array_map(fn($p) => $this->esc(trim($p)), $partes);
        return implode('<br><span style="display:block;margin:8px 0 4px;border-top:1px solid #e2e8f0;"></span>', $seguros);
    }

    private function enviar(string $to, string $subject, string $htmlBody): bool
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $this->host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->user;
            $mail->Password = $this->pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->port;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($this->user, $this->fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService: ' . $e->getMessage());
            return false;
        }
    }
}
