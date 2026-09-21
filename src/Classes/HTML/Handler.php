<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\HTML;

use function sprintf;
use function is_null;
use function is_string;
use function is_array;
use function is_bool;

/**
 * Class Handler
 *
 * A class for generating and handling HTML elements.
 */
class Handler
{
	/**
	 * Generate an HTML input tag based on the specified type and attributes.
	 *
	 * @param string $type The type of input (e.g., text, radio, select).
	 * @param string $name The name attribute for the input element.
	 * @param string $value The value attribute for the input element.
	 * @param array $list An optional list of values for select or radio inputs.
	 * 
	 * @return string The generated HTML input tag.
	 */
	public static function getInputTag(string $type, string $name, string $value, array $list = []): string
	{
		$html = "";

		switch ($type) {
			case "option":
				foreach ($list as $val) {
					$html .= "<option " . (($value == $val) ? "selected " : "") . "value=\"{$val}\">{$val}</option>";
				}

				$html = "<select name=\"{$name}\">{$html}</select>";
				break;
			case "textarea":
				$html = "<textarea rows=\"4\" cols=\"50\" name=\"{$name}\" value=\"{$value}\">{$value}</textarea>";
				break;
			case "color":
				$html = "<input type=\"color\" name=\"{$name}\" value=\"{$value}\">";
				break;
			case "date":
				$html = "<input type=\"date\" name=\"{$name}\" value=\"{$value}\">";
				break;
			case "datetime-local":
				$html = "<input type=\"datetime-local\" name=\"{$name}\" value=\"{$value}\">";
				break;
			case "month":
				$html = "<input type=\"month\" name=\"{$name}\" value=\"{$value}\">";
				break;
			case "number":
				$html = "<input type=\"number\" name=\"{$name}\" value=\"{$value}\">";
				break;
			case "password":
				$html = "<input type=\"password\" name=\"{$name}\" value=\"{$value}\">";
				break;
			case "time":
				$html = "<input type=\"time\" name=\"{$name}\" value=\"{$value}\">";
				break;
			case "week":
				$html = "<input type=\"week\" name=\"{$name}\" value=\"{$value}\">";
				break;
			case "file":
				$html = "<input type=\"file\" name=\"{$name}\" value=\"{$value}\">";
				break;
			case "radio":
				foreach ($list as $val) {
					$html .= "<input " . (($value == $val) ? "checked " : "") . "name=\"{$name}\" type=\"radio\" value=\"{$val}\">{$val}</input>";
				}

				break;
			default:
				$html = "<input style=\"width:100%\" type=\"text\" class=\"text itx\" name=\"{$name}\" value=\"{$value}\"></input>";
				break;
		}

		return $html;
	}

	/**
	 * Generate a parameter string from an associative array.
	 *
	 * @param array $attributes An associative array of attributes.
	 * 
	 * @return string The generated parameter string.
	 */
	public static function generateParameter(array $attributes = []): string
	{
		$result = '';

		foreach ($attributes as $key => $val) {
			$pair = sprintf("'%s'", $val);
			if ($result) {
				$result = $result . ',' . $pair;
			} else {
				$result = $pair;
			}
		}

		return $result;
	}

	/**
	 * Decode HTML entities in a string
	 * 
	 * @param string|null $string
	 * @param int $flags
	 * @param string|null $encoding
	 * 
	 * @return string|null
	 */
	public static function decodeEntity(?string $string = "", int $flags = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, string|null $encoding = null): ?string
	{
		if (is_null($string)) {
			return $string;
		}

		return html_entity_decode($string, $flags, $encoding);
	}

	/**
	 * Generate audio tag
	 * 
	 * @param string $source
	 * @param array $tags
	 * 
	 * @return string
	 */
	public static function generateAudioTag(string $source, array $tags = []): string
	{
		$tags = array_merge(['src' => $source], $tags);

		return self::generateElement('audio', '', $tags, false);
	}

	/**
	 * Generate HTML element
	 * 
	 * @param string $type
	 * @param string $content
	 * @param array|string $attributes
	 * @param bool $close
	 * 
	 * @return string
	 */
	public static function generateElement(string $type, string $content, array|string $attributes = [], bool $close = true): string
	{
		$html = sprintf('%s%s', '<', $type);

		if (empty($attributes)) {
			$html .= '';
		} else if (is_string($attributes)) {
			$html .= ' ' . $attributes;
		} else if (is_array($attributes)) {
			foreach ($attributes as $key => $val) {
				if (isset($key) && !is_bool($val)) {
					$pairs[] = sprintf('%s="%s"', $key, $val);
				} else if (isset($key) && is_bool($val) && $val) {
					$pairs[] = sprintf('%s', $key);
				}
			}

			$html .= ' ' . implode(' ', $pairs);
		}

		return !$close ? sprintf('%s>', $html) : sprintf('%s>%s</%s>', $html, $content, $type);
	}
}
