<?php

namespace MkyCore\Mail;

use MkyCore\Interfaces\MailerTemplateInterface;

class MailerMessage extends \Swift_Message
{

    /**
     * @param MailerTemplateInterface $template
     * @param bool $withText
     * @return MailerMessage
     */
    public function setTemplate(MailerTemplateInterface $template, bool $withText = false): MailerMessage
    {
        $res = $this->setBody($template->generate(), 'text/html');
        if($withText){
            $res->addPart($template->generate(true));
        }
        return $res;
    }
}