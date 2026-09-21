<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\XMPP;

use Clover\Classes\HTML\Handler as HTMLHandler;

use Clover\Classes\ClientURL;

class Bosh
{
    private $user = "";

    private $password = "";

    private $domain;

    private $rid;

    private $sid;

    private $bosh_server = "";

    public function __construct($user, $password, $domain)
    {
        $this->user = $user;
        $this->password = $password;
        $this->domain = $domain;
    }

    public function sendBody($body)
    {
        $cURL = new ClientURL();
        $cURL->option
            ->setURL($this->bosh_server)
            ->setPostMethod(true)
            ->setPostField($body)
            ->setContentTypeXML('text/xml')
            ->setCharset('UTF-8')
            ->setFollowRedirects(true)
            ->setVerbose(true)
            ->setReturnTransfer(true);

        return $cURL->execute();
    }

    public function getPlainHash()
    {
        $hash_value = sprintf("%s@%s\0%s\0%s", $this->user, $this->domain, $this->user, $this->password);
        $hash = base64_encode($hash_value) . "\n";

        return $hash;
    }

    public function getSASLAuth($hash, $mechanism = 'PLAIN')
    {
        $auth_attributes = [
            "xmlns" => "'urn:ietf:params:xml:ns:xmpp-sasl'",
            "mechanism" => "'$mechanism'"
        ];

        $iq_element = HTMLHandler::generateElement("iq", $hash, $auth_attributes, false);

        $attributes = [
            "rid" => "'$this->rid'",
            "xmlns" => "'http://jabber.org/protocol/httpbind'",
            "sid" => "'$this->sid'"
        ];

        return HTMLHandler::generateElement("body", $iq_element, $attributes, false);
    }

    public function getSessionAuth2()
    {
        $session_attributes = [
            "xmlns" => "'urn:ietf:params:xml:ns:xmpp-session'"
        ];

        $session_element = HTMLHandler::generateElement("session", "", $session_attributes, true);

        $iq_attributes = [
            "type" => "'set'",
            "id" => "'_session_auth_2'",
            "xmlns" => "'jabber:client'",

        ];

        $iq_element = HTMLHandler::generateElement("iq", $session_element, $iq_attributes, false);

        $attributes = [
            "rid" => "'$this->rid'",
            "xmlns" => "'http://jabber.org/protocol/httpbind'",
            "sid" => "'$this->sid'"
        ];

        return HTMLHandler::generateElement("body", $iq_element, $attributes, false);
    }

    public function getBindAuth2()
    {
        $bind_attributes = [
            "xmlns" => "'urn:ietf:params:xml:ns:xmpp-bind'"
        ];

        $bind_element = HTMLHandler::generateElement("bind", "", $bind_attributes, true);

        $iq_attributes = [
            "type" => "'set'",
            "id" => "'_bind_auth_2'",
            "xmlns" => "'jabber:client'",

        ];

        $iq_element = HTMLHandler::generateElement("iq", $bind_element, $iq_attributes, false);

        $attributes = [
            "rid" => "'$this->rid'",
            "xmlns" => "'http://jabber.org/protocol/httpbind'",
            "sid" => "'$this->sid'"
        ];

        return HTMLHandler::generateElement("body", $iq_element, $attributes, false);
    }

    public function getMessageBody($to, $message)
    {
        $message_body_element = HTMLHandler::generateElement("body", $message, [], true);

        $message_attributes = [
            "type" => "'groupchat'",
            "to" => "'$to'",
            "xmlns" => "'jabber:client'"
        ];

        $message_element = HTMLHandler::generateElement("message", $message_body_element, $message_attributes, false);

        $attributes = [
            "rid" => "'$this->rid'",
            "sid" => "'$this->sid'",
            "xmlns" => "'http://jabber.org/protocol/httpbind'"
        ];

        return HTMLHandler::generateElement("body", $message_element, $attributes, false);
    }

    public function getRestartBody($to)
    {
        $attributes = [
            "rid" => "'$this->rid'",
            "xmlns" => "'http://jabber.org/protocol/httpbind'",
            "sid" => "'$this->sid'",
            "to" => "'$to'",
            "xml:lang" => "'en'",
            "xmpp:restart" => "'true'",
            "xmlns:xmpp" => "'urn:xmpp:xbosh'"
        ];

        return HTMLHandler::generateElement("body", "", $attributes, true);
    }
}
