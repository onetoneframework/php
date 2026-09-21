<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Dom;

use Clover\Classes\BaseClass;
use DomDocument;

/**
 * Document Class
 *
 * This class provides an interface for working with DOM documents.
 * It allows loading HTML content and managing the DOM structure.
 */
class Document extends BaseClass
{
    private $dom;

    /**
     * Constructor for Document class
     *
     * @param string $encoding The encoding to use (default: 'UTF-8')
     * @param string $version The version number of the document as part of the XML declaration
     */
    public function __construct(string $encoding = 'UTF-8', string $version = "1.0")
    {
        $this->dom = new DomDocument($version, $encoding);
        $this->dom->validateOnParse = true;
    }

    /**
     * Load HTML content into the DOM document
     *
     * @param string $source The HTML source to load
     * @param int $options Options for loading (default: 0)
     * 
     * @return bool
     */
    public function loadHTML(string $source, int $options = 0): bool
    {
        return $this->dom->loadXML($source, $options);
    }
}
