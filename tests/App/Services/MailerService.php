<?php

namespace MkyCore\Tests\App\Services;

class MailerService
{
    public function __construct(private readonly string $toEmail, private string|null $message = null)
    {
    }

    /**
     * @return string
     */
    public function getToEmail(): string
    {
        return $this->toEmail;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function send(): string
    {
        return "Message envoyé: ".$this->message;
    }

}