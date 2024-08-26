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
        $user = $config['mail']['username'];
        $pass = $config['mail']['password'];
        $host = $config['mail']['host'];
        $port = $config['mail']['port'];
        $trasport = $config['mail']['transport'];
        $secure = $config['mail']['secure']; // false, tls, ssl

        $verify = '';
        if ($secure !== false) {
            $verify .= "&crypto=$secure&verify_peer=false&verify_peer_name=false&allow_self_signed=true";
        }

        $transport = Transport::fromDsn("$trasport://$user:$pass@$host:$port?$verify");
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
        array $attachments = []
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

        $email = self::attachFile($email, $attachments);
        $email = self::embedImage($email, $imagesEmbed);

        try {
            $this->mailer->send($email);
            return true;
        } catch (Exception | TransportExceptionInterface $e) {
            throw new Exception($e->getMessage());
        }
    }

    private static function attachFile(Email $email, array $attachments): Email
    {
        foreach ($attachments as $attachment) {
            $email->addPart(new DataPart(new File($attachment)));
        }
        return $email;
    }

    private static function embedImage(Email $email, array $imagesEmbed): Email
    {
        foreach ($imagesEmbed as $image) {
            $email->addPart((new DataPart(new File($image['path']), $image['id'], $image['mime']))->asInline());
        }
        return $email;
    }
}
