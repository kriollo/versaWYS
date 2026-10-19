<?php

declare(strict_types=1);

namespace app\jobs;

use app\services\MailService;
use RuntimeException;
use versaWYS\kernel\ContentType;
use versaWYS\kernel\Job;
use versaWYS\kernel\versaTwig;

/**
 * Un correo enviado desde la cola: la petición web responde sin esperar al servidor SMTP y, si el envío
 * falla, la cola lo reintenta.
 *
 *     Queue::push(EnviarCorreo::class, [
 *         'to' => ['email' => $email, 'name' => $nombre],
 *         'subject' => 'Bienvenido',
 *         'type' => 'twig',                 // twig (por defecto) | html | text
 *         'template' => 'mail/layout',      // con type=twig
 *         'body' => '<p>…</p>',             // con type=html|text
 *         'data' => [...],                  // variables de la plantilla
 *         'images_embed' => [['path' => $ruta, 'id' => 'logo', 'mime' => 'image/png']],
 *     ]);
 */
final class EnviarCorreo implements Job
{
    public function __construct(
        private readonly MailService $mail,
    ) {}

    /** @param array<array-key, mixed> $data */
    #[\Override]
    public function handle(array $data): void
    {
        $to = (array) ($data['to'] ?? []);
        $subject = (string) ($data['subject'] ?? '');
        $type = (string) ($data['type'] ?? 'twig');
        // Una imagen que ya no está en disco no impide el envío
        $images = array_values(array_filter(
            (array) ($data['images_embed'] ?? []),
            static fn(mixed $img): bool => is_array($img) && is_file((string) ($img['path'] ?? '')),
        ));

        if ($type === 'twig') {
            global $twig, $config;
            // En la cola (CLI) Twig no se arranca solo
            $twig ??= new versaTwig($config);
        }

        $sent = $type === 'twig'
            ? $this->mail->enviarTemplate(
                $to,
                $subject,
                (string) ($data['template'] ?? ''),
                (array) ($data['data'] ?? []),
                $images,
            )
            : $this->mail->enviar(
                $to,
                $subject,
                (string) ($data['body'] ?? ''),
                $type === 'text' ? ContentType::TEXT : ContentType::HTML,
                $images,
            );

        if (!$sent) {
            // MailService ya registró el motivo; la excepción hace que la cola lo reintente
            throw new RuntimeException('No se pudo enviar el correo a ' . (string) ($to['email'] ?? ''));
        }
    }
}
