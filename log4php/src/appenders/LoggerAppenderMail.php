<?php
/**
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements. See the NOTICE file distributed with
 * this work for additional information regarding copyright ownership.
 * The ASF licenses this file to You under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * LoggerAppenderMail appends log events via email.
 *
 * This appender does not send individual emails for each logging requests but
 * will collect them in a buffer and send them all in a single email once the
 * appender is closed (i.e. when the script exists). Because of this, it may
 * not appropriate for long running scripts, in which case
 * LoggerAppenderMailEvent might be a better choice.
 *
 * This appender uses a layout.
 *
 * ## Configurable parameters: ##
 *
 * - **to** - Email address(es) to which the log will be sent. Multiple email
 *     addresses may be specified by separating them with a comma.
 * - **from** - Email address which will be used in the From field.
 * - **subject** - Subject of the email message.
 *
 * @version $Revision$
 * @package log4php
 * @subpackage appenders
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @link http://logging.apache.org/log4php/docs/appenders/mail.html Appender documentation
 */
class LoggerAppenderMail extends LoggerAppender
{

    protected $logMailer = null;
    
    /**
     * Email address to put in From field of the email.
     * @var string
     */
    protected $from = null;

    /**
     * The subject of the email.
     * @var string
     */
    protected $subject = 'Log4php Report';

    /**
     * One or more comma separated email addresses to which to send the email.
     * @var string|array
     */
    protected $to = null;

    /**
     * Indiciates whether this appender should run in dry mode.
     * @deprecated
     * @var boolean
     */
    protected $dry = false;

    /**
     * Buffer which holds the email contents before it is sent.
     * @var string
     */
    protected $body = '';

    public function append(LoggerLoggingEvent $event)
    {
        if ($this->layout !== null) {
            $this->body .= $this->layout->format($event);
        }
    }

    public function close()
    {
//        dd($this);
        if ($this->closed != true) {
            $from = $this->from;
            $to = $this->to;

            if (!empty($this->body) and $from !== null and $to !== null and $this->layout !== null) {
                $subject = $this->subject;
                $to = (array) $to;
                if (!$this->dry) {
                    if($this->logMailer){
                        if(!class_exists($this->logMailer)){
                            throw new LoggerException("Class $this->logMailer doestn't exist");
                        }
                        $logMailer = $this->logMailer;
                        $logMailer = new $logMailer($subject, $from, $to, $this->body);
                        if(!($logMailer instanceof \MkyCore\Abstracts\LogMailer)){
                            throw new LoggerException("Class $logMailer must extends MkyCore\Abtracts\LogMailer");
                        }
                        $logMailer->send();
                    }else{
                        $mailer = new \MkyCore\Mail\Mailer();
                        $mailer->buildMessage($subject)
                            ->setBody($this->layout->getHeader() . $this->body . $this->layout->getFooter(), $this->layout->getContentType())
                            ->setTo($to)
                            ->setFrom($from);
                        $mailer->send();
                    }
                } else {
                    echo "DRY MODE OF MAIL APP.: Send mail to: " . join(' and ', $to) . " with content: " . $this->body;
                }
            }
            $this->closed = true;
        }
    }

    /** Sets the 'subject' parameter. */
    public function setSubject($subject)
    {
        $this->setString('subject', $subject);
    }

    /** Returns the 'subject' parameter. */
    public function getSubject()
    {
        return $this->subject;
    }

    /** Sets the 'to' parameter. */
    public function setTo($to)
    {
        if(is_array($to)){
            $this->to = $to;
        }else{
            $this->setString('to', $to);
        }
    }

    /** Returns the 'to' parameter. */
    public function getTo()
    {
        return $this->to;
    }

    /** Sets the 'from' parameter. */
    public function setFrom($from)
    {
        $this->setString('from', $from);
    }

    /** Returns the 'from' parameter. */
    public function getFrom()
    {
        return $this->from;
    }

    /** Enables or disables dry mode. */
    public function setDry($dry)
    {
        $this->setBoolean('dry', $dry);
    }

    public function setLogMailer(string $logMailer)
    {
        $this->logMailer = $logMailer;
    }

    public function getLogMailer()
    {
        return $this->logMailer;
    }
}
