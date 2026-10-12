<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use Exception;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

enum ContentType: string
{
    case HTML = 'html';
    case TEXT = 'text';
    case TWIG = 'twig';
}

class MailSender
{
    private Mailer $mailer;

    public function __construct()
    {
        global $config;
        $user = $config['mail']['username'] ?? '';
        $pass = $config['mail']['password'] ?? '';
        $host = $config['mail']['host'] ?? '';
        $port = $config['mail']['port'] ?? 0;
        $transportScheme = $config['mail']['transport'] ?? 'smtp';
        $secure = $config['mail']['secure'] ?? false; // false, tls, ssl

        if ($secure === 'ssl') {
            $transportScheme = 'smtps';
        }

        $auth = '';
        if ($user !== '') {
            $auth = rawurlencode($user) . ':' . rawurlencode($pass) . '@';
        }

        $options = [];
        if ($secure === false || $secure === 'false' || $secure === '') {
            $options['auto_tls'] = false;
        } elseif (($config['mail']['verify_peer'] ?? true) === false) {
            // Solo para servidores SMTP internos con certificado autofirmado (mail.verify_peer = false)
            $options['verify_peer'] = false;
            $options['verify_peer_name'] = false;
            $options['allow_self_signed'] = true;
        }

        $query = http_build_query($options, '', '&', PHP_QUERY_RFC3986);
        $dsn = sprintf('%s://%s%s:%s%s', $transportScheme, $auth, $host, $port, $query !== '' ? "?{$query}" : '');

        $transport = Transport::fromDsn($dsn);
        $this->mailer = new Mailer($transport);
    }

    /**
     * @throws Exception
     */
    public function send(
        array $to,
        string $subject,
        string $body,
        ContentType $type,
        array $context = [],
        array $imagesEmbed = [],
        array $attachments = [],
    ): bool {
        global $config, $twig;

        $email = (new EMail())
            ->from(new Address($config['mail']['username'], $config['mail']['name_from']))
            ->to(new Address($to['email'], $to['name']))
            ->subject($subject);

        match ($type) {
            ContentType::HTML => $email->html($body),
            ContentType::TEXT => $email->text($body),
            ContentType::TWIG => $email->html($twig->render($body, $context)),
        };

        $email = $this->attachFile($email, $attachments);
        $email = $this->embedImage($email, $imagesEmbed);

        try {
            $this->mailer->send($email);

            return true;
        } catch (Exception|TransportExceptionInterface $e) {
            $error_message = 'Error al enviar email: ' . $e->getMessage();
            Loggin::error($error_message, 'MailSender');

            // Nunca romper el flujo principal por falla de SMTP.
            return false;
        }
    }

    private function attachFile(Email $email, array $attachments): Email
    {
        foreach ($attachments as $attachment) {
            $email->addPart(new DataPart(new File($attachment)));
        }

        return $email;
    }

    private function embedImage(Email $email, array $imagesEmbed): Email
    {
        foreach ($imagesEmbed as $image) {
            $cid = trim((string) ($image['id'] ?? ''));
            if ($cid === '') {
                $cid = bin2hex(random_bytes(8)) . '@symfony';
            } elseif (!str_contains($cid, '@')) {
                $cid .= '@symfony';
            }

            $part = (new DataPart(
                new File($image['path']),
                basename((string) $image['path']),
                $image['mime'],
            ))->asInline();
            $part->setContentId($cid);
            $email->addPart($part);
        }

        return $email;
    }
}
