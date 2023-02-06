<?php

namespace MkyCore\Mail;

use MkyCore\Facades\Request;
use MkyCore\Facades\View;
use MkyCore\Interfaces\MailerTemplateInterface;
use MkyCore\Str;
use Swift_DependencyException;
use Swift_Mailer;
use Swift_Message;
use Swift_Transport;

class Mailer
{
    private readonly Swift_Mailer $mailer;
    private ?Swift_Message $message = null;

    public function __construct()
    {
        $this->mailer = new Swift_Mailer($this->swiftTransport());
    }

    private function swiftTransport(): Swift_Transport
    {
        $parseUrl = $this->parseDSN();
        $system = $parseUrl['scheme'] ?? 'smtp';
        $system = ucfirst($system);
        $classTransport = "Swift_{$system}Transport";
        $transport = new $classTransport();

        if (isset($parseUrl['host'])) {
            $transport->setHost($parseUrl['host']);
        }
        if (isset($parseUrl['port'])) {
            $transport->setPort($parseUrl['port']);
        }
        if (isset($parseUrl['host'])) {
            $transport->setHost($parseUrl['host']);
        }
        if (isset($parseUrl['user'])) {
            $transport->setUsername($parseUrl['user']);
        }
        if (isset($parseUrl['pass'])) {
            $transport->setPassword($parseUrl['pass']);
        }
        if (isset($parseUrl['query'])) {
            foreach ($parseUrl['query'] as $key => $value) {
                $key = Str::classify($key);
                if (method_exists($transport, "set$key")) {
                    $transport->{"set$key"}($value);
                } else {
                    if ($key == 'AuthMode') {
                        $transport->{"set$key"}($value);
                    }
                }
            }
        }
        return $transport;
    }

    private function parseDSN(): array
    {
        $parseUrl = parse_url(env('MAILER_DSN'));
        parse_str($parseUrl['query'], $parseUrl['query']);
        return $parseUrl;
    }

    /**
     * @param string|null $subject
     * @param string|null $body
     * @param string|null $contentType
     * @param string|null $charset
     * @return MailerMessage
     * @throws Swift_DependencyException
     */
    public function buildMessage(string $subject = null, string $body = null, string $contentType = null, string $charset = null): MailerMessage
    {
        $this->message = new MailerMessage($subject, $body, $contentType, $charset);
        return $this->message;
    }
    
    public function message(MailerMessage $message): static
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @param string $view
     * @param array $params
     * @return string
     */
    public function render(string $view, array $params = []): string
    {
        $params = array_replace_recursive($params, [
            'app_url' => env('APP_URL', Request::baseUri())
        ]);
        return View::toHtml($view, $params);
    }

    public function send(): int
    {
        return $this->mailer->send($this->message);
    }

    /**
     * @return Swift_Mailer
     */
    public function getMailer(): Swift_Mailer
    {
        return $this->mailer;
    }

    /**
     * @param MailerTemplateInterface $mailerTemplate
     * @return MailerTemplateInterface
     */
    public function useTemplate(MailerTemplateInterface $mailerTemplate = new MailerTemplate()): MailerTemplateInterface
    {
        return $mailerTemplate;
    }
}