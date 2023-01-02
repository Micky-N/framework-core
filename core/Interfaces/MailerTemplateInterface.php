<?php

namespace MkyCore\Interfaces;

interface MailerTemplateInterface
{
    /**
     * Generate mail view
     * HTML or Txt
     *
     * @param bool $text
     * @return string
     */
    public function generate(bool $text = false): string;

    /**
     * Set views file
     * @example template.twig
     * if array ['twig' => 'template.twig', 'text' => 'template.txt']
     *
     * @param array|string $viewTemplates
     * @return MailerTemplateInterface
     */
    public function use(array|string $viewTemplates): MailerTemplateInterface;
}