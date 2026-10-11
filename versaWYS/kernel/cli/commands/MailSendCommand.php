<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli\commands;

use app\services\MailService;
use JsonException;
use Throwable;
use versaWYS\kernel\cli\BaseCommand;
use versaWYS\kernel\Loggin;
use versaWYS\kernel\versaTwig;

/**
 * Comando universal de envío de correos electrónicos.
 *
 * Uso:
 *   php versaCLI mail:send '<payload_base64>'
 *
 * El payload viaja en base64 para evitar problemas de escapado en Windows.
 *
 * Payload JSON (decodificado):
 *   {
 *     "to":           { "email": "dest@ejemplo.com", "name": "Nombre" },
 *     "subject":      "Asunto del correo",
 *     "type":         "twig",                          // twig (default) | html | text
 *     "template":     "mail/cita_confirmacion",        // requerido si type=twig
 *     "body":         "<h1>HTML directo</h1>",         // requerido si type=html|text
 *     "data":         { "variable": "valor" },         // variables para plantilla Twig
 *     "images_embed": [                                // imágenes CID para embed
 *       { "path": "/ruta/logo.png", "id": "logo_empresa", "mime": "image/png" }
 *     ]
 *   }
 */
class MailSendCommand extends BaseCommand
{
    private const LOG_MODULE = 'mail-send';

    #[\Override]
    public function execute(array $params): int
    {
        Loggin::debug('Comando mail:send iniciado', self::LOG_MODULE, [
            'params_count' => count($params),
            'param_0_len' => isset($params[0]) ? strlen((string) $params[0]) : 0,
        ]);

        // ── 1. Decodificar base64 y parsear JSON ─────────────────────────────
        $jsonRaw = null;

        try {
            $base64Raw = $this->requireParam($params, 0, 'payload base64');

            $jsonRaw = base64_decode($base64Raw, true);
            if ($jsonRaw === false) {
                Loggin::error('base64_decode falló: el parámetro no es base64 válido', self::LOG_MODULE, [
                    'raw_preview' => substr($base64Raw, 0, 100),
                ]);
                $this->error('Payload base64 inválido');

                return 1;
            }

            Loggin::debug('base64 decodificado', self::LOG_MODULE, ['json_len' => strlen($jsonRaw)]);

            $payload = json_decode($jsonRaw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Loggin::error('JSON inválido tras decodificar base64', self::LOG_MODULE, [
                'error' => $e->getMessage(),
                'raw_preview' => substr($jsonRaw ?? '', 0, 300),
            ]);
            $this->error('JSON inválido: ' . $e->getMessage());

            return 1;
        }

        $to = $payload['to'] ?? null;
        $subject = trim($payload['subject'] ?? '');
        $type = $payload['type'] ?? 'twig'; // twig | html | text
        $template = trim($payload['template'] ?? '');
        $body = trim($payload['body'] ?? '');
        $data = $payload['data'] ?? [];
        $imagesRaw = $payload['images_embed'] ?? [];

        Loggin::debug('Payload parseado', self::LOG_MODULE, [
            'to_email' => $to['email'] ?? 'NO DEFINIDO',
            'to_name' => $to['name'] ?? '',
            'subject' => $subject,
            'type' => $type,
            'template' => $template ?: '(sin template)',
            'data_keys' => array_keys($data),
            'images_count' => count($imagesRaw),
        ]);

        // ── 2. Validar campos requeridos ─────────────────────────────────────
        if (!is_array($to) || empty($to['email'])) {
            Loggin::error('Payload sin to.email', self::LOG_MODULE, ['payload_keys' => array_keys($payload)]);
            $this->error('Campo requerido faltante: to.email');

            return 1;
        }

        if ($subject === '') {
            Loggin::error('Payload sin subject', self::LOG_MODULE, ['to' => $to['email']]);
            $this->error('Campo requerido faltante: subject');

            return 1;
        }

        if ($type === 'twig' && $template === '') {
            Loggin::error('Payload sin template (requerido para type=twig)', self::LOG_MODULE, ['to' => $to['email']]);
            $this->error('Campo requerido faltante: template (requerido cuando type=twig)');

            return 1;
        }

        if (in_array($type, ['html', 'text'], true) && $body === '') {
            Loggin::error('Payload sin body (requerido para type=' . $type . ')', self::LOG_MODULE, [
                'to' => $to['email'],
            ]);
            $this->error('Campo requerido faltante: body (requerido cuando type=html|text)');

            return 1;
        }

        // ── 3. Inicializar Twig si es necesario (contexto CLI) ────────────────
        // En CLI, $twig no se inicializa en el bootstrap (solo lo hace index.php).
        // MailService::enviarTemplate() necesita global $twig para renderizar plantillas.
        if ($type === 'twig') {
            global $twig, $config;
            if ($twig === null) {
                Loggin::debug('Inicializando Twig para contexto CLI', self::LOG_MODULE);
                $twig = new versaTwig($config);
            }
        }

        // ── 4. Construir array de imágenes embed (validar existencia en disco) ─
        $imagenesEmbed = [];

        foreach ($imagesRaw as $img) {
            $imgPath = $img['path'] ?? '';
            $imgId = $img['id'] ?? '';
            $imgMime = $img['mime'] ?? 'image/png';

            if ($imgPath === '' || $imgId === '') {
                Loggin::warning('Entrada images_embed ignorada: path o id vacíos', self::LOG_MODULE, [
                    'img' => $img,
                ]);

                continue;
            }

            if (!file_exists($imgPath)) {
                Loggin::warning('Imagen embed no encontrada en disco, se omite', self::LOG_MODULE, [
                    'path' => $imgPath,
                    'id' => $imgId,
                ]);

                continue;
            }

            $imagenesEmbed[] = ['path' => $imgPath, 'id' => $imgId, 'mime' => $imgMime];
        }

        // ── 5. Enviar correo ──────────────────────────────────────────────────
        Loggin::info('Iniciando envío de correo', self::LOG_MODULE, [
            'to' => $to['email'],
            'subject' => $subject,
            'type' => $type,
            'template' => $template ?: '(html/text directo)',
            'images_count' => count($imagenesEmbed),
        ]);

        try {
            $mailService = new MailService();

            if ($type === 'twig') {
                $enviado = $mailService->enviarTemplate($to, $subject, $template, $data, $imagenesEmbed);
            } else {
                $contentType = $type === 'text'
                    ? \versaWYS\kernel\ContentType::TEXT
                    : \versaWYS\kernel\ContentType::HTML;
                $enviado = $mailService->enviar($to, $subject, $body, $contentType, $imagenesEmbed);
            }
        } catch (Throwable $e) {
            Loggin::error('Excepción al enviar correo', self::LOG_MODULE, [
                'to' => $to['email'],
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('Excepción al enviar: ' . $e->getMessage());

            return 1;
        }

        // ── 6. Resultado ──────────────────────────────────────────────────────
        if ($enviado) {
            Loggin::info('Correo enviado exitosamente', self::LOG_MODULE, [
                'to' => $to['email'],
                'subject' => $subject,
                'type' => $type,
            ]);
            $this->success('Correo enviado a ' . $to['email']);

            return 0;
        }

        Loggin::error('MailService retornó false', self::LOG_MODULE, [
            'to' => $to['email'],
            'subject' => $subject,
            'type' => $type,
            'data_keys' => array_keys($data),
        ]);
        $this->error('Falló el envío del correo a ' . $to['email']);

        return 1;
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Comando universal de envío de correo: soporta Twig, HTML directo e imágenes embed CID';
    }

    #[\Override]
    public function getHelp(): string
    {
        return <<<'HELP'
            Comando: mail:send

            Descripción:
              Comando universal para envío de correo electrónico en background.
              Acepta el payload JSON codificado en base64 para evitar problemas de escapado.
              Soporta plantillas Twig con imágenes embed CID, HTML directo y texto plano.

            Uso:
              php versaCLI mail:send '<payload_base64>'

            Payload JSON (campos disponibles):
              - to.email        (requerido): Email del destinatario
              - to.name         (opcional):  Nombre del destinatario
              - subject         (requerido): Asunto del correo
              - type            (opcional):  "twig" (default) | "html" | "text"
              - template        (si twig):   Ruta de plantilla Twig (ej: "mail/layout")
              - body            (si html/text): Cuerpo del correo
              - data            (si twig):   Variables para la plantilla (objeto JSON)
              - images_embed    (opcional):  Array de imágenes para embed CID
                  [{"path":"/ruta/logo.png","id":"logo_empresa","mime":"image/png"}]

            Ejemplos:
              # Twig con logo embed
              php versaCLI mail:send "$(echo '{...json...}' | base64 -w0)"

              # HTML directo
              php versaCLI mail:send "$(echo '{"to":{"email":"user@test.com"},"subject":"Test","type":"html","body":"<h1>Hola</h1>"}' | base64 -w0)"
            HELP;
    }
}
