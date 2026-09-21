<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Mail;
use function sprintf;

/**
 * Class SimpleMailer
 *
 * @package Clover\Classes\Mail
 */
class SimpleMailer
{
    private string $subject;
    private string $message;
    private string $from;
    private string $to;

    /**
     * SimpleMailer constructor.
     *
     * @param string $from
     * @param string $to
     */
    public function __construct(string $from, string $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * Set the email message.
     *
     * @param string $message
     * 
     * @return void
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * Set the email subject.
     *
     * @param string $subject
     * 
     * @return void
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * Send the email.
     *
     * @return bool
     */
    public function send(): bool
    {
        $headers = [
            sprintf("From: %s", $this->from),
            sprintf("Reply-To: %s", $this->from),
            "X-Mailer: PHP/" . phpversion()
        ];

        return mail($this->to, $this->subject, $this->message, $headers);
    }

}