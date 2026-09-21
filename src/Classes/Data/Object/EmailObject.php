<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use Clover\Classes\Data\StringObject as StringObject;

use function in_array;
use function is_array;

/**
 * Class EmailObject
 *
 * Represents an email address and provides methods to validate and extract components.
 */
#[\AllowDynamicProperties]
class EmailObject extends StringObject
{
    /**
     * The raw email address string.
     *
     * @var mixed
     */
    protected $rawData;

    /**
     * List of known email domains.
     *
     * @var mixed
     */
    private static $knownDomains;

    /**
     * EmailObject constructor.
     *
     * @param mixed $data The email address data.
     * 
     * @return void
     */
    public function __construct($data)
    {
        self::$knownDomains = require(__DIR__ . '/../../../Defaults/EmailDomain.php');

        $this->rawData = $data;

        parent::__construct($data);
    }

    /**
     * Convert the EmailObject to a string representation.
     *
     * @return string The email address as a string.
     */
    public function __toString(): string
    {
        return (string) $this->rawData;
    }

    /**
     * Check if the email's domain is in the list of known domains.
     *
     * @return bool True if the domain is known, false otherwise.
     */
    public function isKnownDomain(): bool
    {
        $domain = $this->getDomain();

        return in_array($domain, self::$knownDomains);
    }

    /**
     * Get the domain part of the email address.
     *
     * @return StringObject The domain part of the email.
     */
    public function getDomain(): StringObject
    {
        preg_match("/([a-zA-Z0-9]+)@((?:[a-zA-Z0-9\-_]+\.)+[a-zA-Z]{2,})/i", $this->rawData, $matches);
        $data = empty($matches) ? "" : (is_array($matches) ? $matches[2] : "");

        return new StringObject($data);
    }

    /**
     * Get the name part of the email address.
     *
     * @return StringObject The name part of the email.
     */
    public function getName(): StringObject
    {
        preg_match("/([a-zA-Z0-9]+)@((?:[a-zA-Z0-9\-_]+\.)+[a-zA-Z]{2,})/i", $this->rawData, $matches);
        $data = empty($matches) ? "" : (is_array($matches) ? $matches[1] : "");

        return new StringObject($data);
    }
}
