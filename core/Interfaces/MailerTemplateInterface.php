<?php

namespace MkyCore\Interfaces;

interface MailerTemplateInterface
{
    /**
     * Generate mail view in HTML
     *
     * @return string
     */
    public function generate(): string;

    /**
     * Generate mail view in Plain text
     * return false to not use
     *
     * @return string|false
     */
    public function generateText(): string|false;
}