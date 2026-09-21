<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\XML;

use Clover\Classes\OperationSystem;

class XMLParser
{

	private \XMLParser $parser;

	public function __construct(string|null $encoding = null)
	{
		$this->parser = xml_parser_create($encoding);

		if (OperationSystem::comparePHPVersion('8.4.0', '<')) {
			$this->setObject();
		}
	}

	public function setObject(): bool
	{
		if (!function_exists('xml_set_object')) {
			throw new \RuntimeException('XML extension is not available.');
		}
		
		return xml_set_object($this->parser, $this);
	}

	public function parse($plainText): int
	{
		return xml_parse($this->parser, $plainText);
	}

	public function freeParser(): bool
	{
		return xml_parser_free($this->parser);
	}

	public function setElementHandler(callable $startTag, callable $endTag): bool
	{
		return xml_set_element_handler($this->parser, $startTag, $endTag);
	}

	public function setCharacterDataHandler($object): bool
	{
		return xml_set_character_data_handler($this->parser, $object);
	}
}
