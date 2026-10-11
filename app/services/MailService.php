<?php

declare(strict_types=1);

namespace app\services;

use Throwable;
use versaWYS\kernel\ContentType;
use versaWYS\kernel\Loggin;
use versaWYS\kernel\MailSender;

/**
 * Servicio genérico de envío de correos.
 *
 * Encapsula MailSender para uso en controladores y servicios.
 * Atrapa excepciones y registra errores sin interrumpir el flujo principal.
 *
 * Uso básico (HTML):
 *   $mail = new MailService();
 *   $mail->enviar(['email' => 'x@x.com', 'name' => 'X'], 'Asunto', '<b>Cuerpo</b>');
 *
 * Uso con plantilla Twig:
 *   $mail->enviarTemplate(
 *       ['email' => 'x@x.com', 'name' => 'X'],
 *       'Asunto',
 *       'mail/mi_plantilla',
 *       ['variable' => 'valor'],
 *   );
 */
class MailService
{
    private MailSender $sender;

    public function __construct()
    {
        $this->sender = new MailSender();
    }

    /**
     * Envía un correo con cuerpo HTML o texto plano.
     *
     * @param  array  $to  Destinatario: ['email' => '...', 'name' => '...']
     * @param  string  $asunto  Asunto del correo
     * @param  string  $cuerpo  Cuerpo del correo (HTML o texto)
     * @param  ContentType  $tipo  Tipo de contenido (HTML o TEXT)
     */
    public function enviar(
        array $to,
        string $asunto,
        string $cuerpo,
        ContentType $tipo = ContentType::HTML,
        array $imagenesEmbed = [],
    ): bool {
        try {
            return $this->sender->send($to, $asunto, $cuerpo, $tipo, [], $imagenesEmbed);
        } catch (Throwable $throwable) {
            Loggin::error('Error enviando correo: ' . $throwable->getMessage(), 'MailService', [
                'to' => $to['email'] ?? '',
                'subject' => $asunto,
            ]);

            return false;
        }
    }

    /**
     * Envía un correo usando una plantilla Twig.
     *
     * @param  array  $to  Destinatario: ['email' => '...', 'name' => '...']
     * @param  string  $asunto  Asunto del correo
     * @param  string  $template  Ruta de la plantilla Twig (ej: 'mail/cita_confirmacion')
     * @param  array  $contexto  Variables disponibles en la plantilla
     * @param  array  $imagenesEmbed  Imágenes embebidas: [['path' => '...', 'id' => '...', 'mime' => '...']]
     */
    public function enviarTemplate(
        array $to,
        string $asunto,
        string $template,
        array $contexto = [],
        array $imagenesEmbed = [],
    ): bool {
        try {
            return $this->sender->send($to, $asunto, $template, ContentType::TWIG, $contexto, $imagenesEmbed);
        } catch (Throwable $throwable) {
            Loggin::error('Error enviando correo con plantilla: ' . $throwable->getMessage(), 'MailService', [
                'to' => $to['email'] ?? '',
                'subject' => $asunto,
                'template' => $template,
            ]);

            return false;
        }
    }
}
