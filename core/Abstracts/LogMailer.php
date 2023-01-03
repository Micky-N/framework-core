<?php

namespace MkyCore\Abstracts;

abstract class LogMailer
{
    public function __construct(protected string $subject, protected string|array $from, protected string|array $to, protected string $body = '')
    {
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return array|string
     */
    public function getFrom(): array|string
    {
        return $this->from;
    }

    /**
     * @param array|string $from
     */
    public function setFrom(array|string $from): void
    {
        $this->from = $from;
    }

    /**
     * @return array|string
     */
    public function getTo(): array|string
    {
        return $this->to;
    }

    /**
     * @param array|string $to
     */
    public function setTo(array|string $to): void
    {
        $this->to = $to;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * Send log mail
     * 
     * @return int
     */
    abstract public function send(): int;
}