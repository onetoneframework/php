<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use Clover\Classes\Data\StringHandler;

/** 
 * HTMLHandler provides methods to handle HTML data, including escaping, filtering, and converting special characters.
 */
class HTMLHandler extends StringHandler
{
	/** 
	 * Generate a URL parameter string based on the provided key-value pairs.
	 * If the first argument is null, it will generate a URL parameter string from the current $_GET parameters.
	 * Otherwise, it will use the provided key-value pairs to construct the URL parameter string.
	 *
	 * @return string The generated URL parameter string.
	 */
	public static function getUrlParameter(): string
	{
		$parameter = '';
		$func_num = \func_num_args();
		$func_get = \func_get_args();
		
		if ($func_get[0] === null) {
			for ($i = 1; $i < $func_num; $i += 2) {
				$paramKey = $func_get[$i] ?? '';
				$paramValue = $func_get[$i + 1] ?? '';
				$parameter .= ($parameter ? '&' : '?') . "$paramKey=$paramValue";
			}
		} else {
			$get_tmp = $_GET;
			
			for ($i = 0; $i < $func_num; $i += 2) {
				$paramKey = $func_get[$i] ?? '';
				$paramValue = $func_get[$i + 1] ?? '';
				
				if ($paramValue === '') {
					unset($get_tmp[$paramKey]);
				} else {
					$get_tmp[$paramKey] = $paramValue;
				}
			}
			
			foreach ($get_tmp as $key => $val) {
				$parameter .= ($parameter ? '&' : '?') . "$key=$val";
			}
		}
		$parameter = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] . $parameter : $parameter;

		return $parameter;
	}

	/** 
	 * Escape special characters in a string by adding backslashes.
	 * This is useful for preparing strings for use in contexts where special characters may cause issues, such as in database queries or HTML output.
	 *
	 * @param string $string The input string to be escaped.
	 * @return string The escaped string with special characters prefixed by backslashes.
	 */
	public static function escapeSlash($string): string
	{
		return stripslashes($string);
	}

	/** 
	 * Remove all newline characters and tabs from the input string, replacing tabs with a single space.
	 * This is useful for normalizing strings by eliminating unnecessary whitespace and line breaks.
	 *
	 * @param string $input The input string to be processed.
	 * @return string The processed string with newlines removed and tabs replaced by spaces.
	 */
	public static function nlTrim($input): array|string|null
	{
		$input = preg_replace('/[\r\n]/', '', $input);
		$input = preg_replace('/\t+/', ' ', $input);
		return $input;
	}

	/** 
	 * Limit the number of consecutive newlines in the input string to a specified maximum.
	 * This is useful for preventing excessive blank lines in text content while preserving some formatting.
	 *
	 * @param string $input The input string to be processed.
	 * @param int $max The maximum number of consecutive newlines allowed (default is 2).
	 * @return string The processed string with limited consecutive newlines.
	 */
	public static function nlslim($input, $max = 2): array|string
	{
		$input = mb_ereg_replace('[\t 　]+(?=[\r\n])', '', $input);
		$replace = str_repeat('$1', $max);
		++$max;
		$regexp = '/(\r\n?|\n) {' . $max . ',}/';
		$input = preg_replace($regexp, $replace, $input);
		$input = str_replace("\t", '    ', $input);
		return $input;
	}

	/** 
	 * Filter a variable based on a specified type, applying various validation and sanitization rules.
	 * The function supports multiple types, including MaxLength, Bracket, StringNumber, PhoneNumber, URL, Email, URLParameter, Label, FunctionName, Deny, DoubleQuotation, SinigleQuotation, WithOutHTML, JSON, Numbers, Number, String, Integer, Float, and Boolean.
	 *
	 * @param mixed $string The input variable to be filtered.
	 * @param string $type The type of filtering to be applied.
	 * @return mixed The filtered variable based on the specified type. It may return false if the input does not meet the criteria for the given type.
	 */
	public static function filterVariable(mixed $string, $type): mixed
	{
		switch ($type) {
			case (preg_match('/^MaxLength\((.*\))$/', $type, $matches)):
				if (strlen($string) > $matches[1]) {
					$string = false;
				}

				break;
			case (preg_match('/^Bracket\((.*\))$/', $type, $matches)):
				if (isset($matches[1])) {
					$regex = $matches[1];
					if (preg_match('/^[A-Za-z0-9]+$/i', $regex, $matches)) {
						$string = $matches[1];
						$regex = '/^<' . $string . '>([\s\S]*?)<\/' . $string . '>$/i';
					}

					if ($regex && preg_match($regex, $string, $matches)) {
						if (isset($matches[1])) {
							$string = $matches[1];
						} else {
							$string = false;
						}
					} else {
						$string = false;
					}
				} else {
					$string = false;
				}

				break;
			case 'StringNumber':
				if (preg_match('/^[A-Za-z0-9]+$/i', $string, $matches)) {
					if (isset($matches[1])) {
						$string = $matches[1];
					} else {
						$string = false;
					}
				} else {
					$string = false;
				}

				break;
			case 'PhoneNumber':
				if (preg_match('/^[0-9]{2,3}-[0-9]{3,4}-[0-9]{4}$/', $string, $matches)) {
					if (isset($matches[1])) {
						$string = $matches[1];
					} else {
						$string = false;
					}
				} else {
					$string = false;
				}

				break;
			case 'URL':
				if (preg_match("/^(http\:\/\/)*[.a-zA-Z0-9-]+\.[a-zA-Z]+$/", $string, $matches)) {
					if (isset($matches[1])) {
						$string = $matches[1];
					} else {
						$string = false;
					}
				} else {
					$string = false;
				}

				break;
			case 'Email':
				if (preg_match("/^[^@]+@[._a-zA-Z0-9-]+\.[a-zA-Z]+$/", $string, $matches)) {
					if (isset($matches[1])) {
						$string = $matches[1];
					} else {
						$string = false;
					}
				} else {
					$string = false;
				}

				break;
			case 'URLParameter':
				if (preg_match('/([^=&?]+)=([^&#]*)/', $string, $matches)) {
					if (count($matches) === 1) {
						if (isset($matches[1])) {
							$string = $matches[1];
						} else {
							$string = false;
						}
					} else if (count($matches) > 1) {
						$string = $matches;
					}
				} else {
					$string = false;
				}

				break;
			case 'Label':
				if (preg_match("/\[([a-zA-Z0-9\s_-]+)\]/i", $string, $matches)) {
					if (isset($matches[1])) {
						$string = $matches[1];
					} else {
						$string = false;
					}
				} else {
					$string = false;
				}

				break;
			case 'FunctionName':
				if (preg_match_all("/(\[?[a-zA-Z0-9\s_-]+\]?)/", $string, $matches)) {
					if (isset($matches[1])) {
						$string = $matches[1];
					} else {
						$string = false;
					}
				} else {
					$string = false;
				}

				break;
			case 'Deny':
				$string = false;

				break;
			case 'DoubleQuotation':
				if (preg_match('/^"(.*)"$/', $string, $matches)) {
					if (isset($matches[1])) {
						$string = $matches[1];
					} else {
						$string = false;
					}
				} else {
					$string = false;
				}

				break;
			case 'SinigleQuotation':
				if (preg_match('/^\'(.*)\'$/', $string, $matches)) {
					if (isset($matches[1])) {
						$string = $matches[1];
					} else {
						$string = false;
					}
				} else {
					$string = false;
				}

				break;
			case 'WithOutHTML':
				$string = strip_tags($string);
				break;
			case 'JSON':
				if (!JSONHandler::isJSON($string)) {
					$string = false;
				}

				break;
			case 'Numbers':
				if (!is_numeric($string) || !is_int($string)) {
					if (preg_match('/^(\d[\d\.]+)$/', $string, $matches)) {
						if (isset($matches[1])) {
							$string = $matches[1];
						} else {
							$string = false;
						}
					} else {
						$string = false;
					}
				}

				break;
			case 'Number':
				if (!is_numeric($string) || !is_int($string)) {
					if (preg_match('/^(\d+)$/', $string, $matches)) {
						if (isset($matches[1])) {
							$string = $matches[1];
						} else {
							$string = false;
						}
					} else {
						$string = false;
					}
				}

				break;
			case 'String':
				if (!is_string($string)) {
					$string = false;
				}

				break;
			case 'Integer':
				$string = intval($string);

				break;
			case 'Float':
				$string = intval($string);
				$string = (float)sprintf('% u', $string);
				if ($string < 0) {
					$string = false;
				}

				break;
			case 'Boolean':
				$string = ($string === true) ? true : (($string === false) ? false : false);

				break;
			default:
				break;
		}

		return $string;
	}

	/** 
	 * Convert special characters in a string to their corresponding HTML entities.
	 * This is useful for preventing XSS attacks and ensuring that special characters are displayed correctly in HTML contexts.
	 *
	 * @param string $string The input string to be converted.
	 * @return string The converted string with special characters replaced by HTML entities.
	 */
	public static function convertSpecialCharactersToHtmlEntities($string)
	{
		return htmlspecialchars($string, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
	}

	/** 
	 * Convert HTML entities in a string back to their corresponding characters.
	 * This is useful for displaying HTML content that has been previously escaped or for processing user input that may contain HTML entities.
	 *
	 * @param string $string The input string containing HTML entities to be converted.
	 * @return string The converted string with HTML entities replaced by their corresponding characters.
	 */
	public static function unhtmlSpecialChars($string)
	{
		$entity = ['&quot;', '&#039;', '&#39;', '&lt;', '&gt;', '&amp;'];
		$symbol = ['"', "'", "'", '<', '>', '&'];
		return str_replace($entity, $symbol, $string);
	}

	/** 
	 * Convert HTML entities in a string back to their corresponding characters, including handling of numeric entities.
	 * This is useful for displaying HTML content that has been previously escaped or for processing user input that may contain HTML entities, including numeric ones.
	 *
	 * @param string $string The input string containing HTML entities to be converted.
	 * @return string The converted string with HTML entities replaced by their corresponding characters.
	 */
	public static function autolink($string)
	{
		$regexp = array(
			'/[a-z\d\-_.+]+@([a-z\d\-]+\.)+[a-z]{2,7}/i',
			'/(?<!")(https?|ftp):\/\/([a-z\d\-]+\.)+[a-z]{2,7}([\w!#$%()*+,\-.\/:;=?&@~\[\]]|&amp|&#039|&#39)*/'
		);

		$anchor = array(
			'<a href="mailto:$0">$0</a>',
			'<a href="$0">$0</a>'
		);

		return preg_replace($regexp, $anchor, $string);
	}

	/** 
	 * Replace matched HTML tags in a string with their corresponding content, while applying specific filtering rules to ensure security and prevent XSS attacks.
	 * The function checks for certain tag names and attributes that may be potentially harmful and either allows or disallows them based on predefined criteria.
	 *
	 * @param array $match An array containing the matched HTML tag, its name, attributes, and content.
	 * @return string The processed string with the matched HTML tag replaced by its content if it passes the filtering rules, or the original tag if it does not.
	 */
	public static function replace($match)
	{
		list($target, $name, $attr) = $match;
		$name = strToLower($name);
		$value = end($match);

		if (strpos($value, '<') !== false) {
			return $target;
		}

		if (preg_match('/script|style|link|html|body|frame/', $name)) {
			return $target;
		}

		if ($attr !== '') {
			if (preg_match('/ on|about:|script:|@import|behaviou?r|binding|boundary|cookie|eval|expression|include-source|xmlhttp/i', $attr)) {
				return $target;
			}

			$attr = str_replace('*/', '*/  ', $attr);
			$attr = str_replace('&quot;', '"', $attr);
			$attr = preg_replace('/ {2,}/', ' ', $attr);
			$attr = str_replace('=" ', '="', $attr);
			$attr = str_replace(' "', '"', $attr);
			$attr = preg_replace('/^ [a-z]+/ie', "strToLower('$0');", $attr);
		}

		return "<$name$attr>$value</$name>";
	}

	/** 
	 * Convert HTML entities in a string back to their corresponding characters, including handling of numeric entities.
	 * This is useful for displaying HTML content that has been previously escaped or for processing user input that may contain HTML entities, including numeric ones.
	 *
	 * @param string $string The input string containing HTML entities to be converted.
	 * @return string The converted string with HTML entities replaced by their corresponding characters.
	 */
	public static function entityToTag($string, $names)
	{
		$attr = ' ([a-z]+)=&quot;([\w!#$%()*+,\-.\/:;=?@~\[\] ]|&amp|&#039|&#39)+&quot;';
		$name_list = explode(',', $names);
		foreach ($name_list as $name) {
			$string = preg_replace_callback("{&lt;($name)(($attr)*)&gt;(.*?)&lt;/$name&gt;}is", array('Utility', 'replace'), $string);
		}

		return $string;
	}

	/** 
	 * Replace matched HTML tags in a string with their corresponding content, while applying specific filtering rules to ensure security and prevent XSS attacks.
	 * The function checks for certain tag names and attributes that may be potentially harmful and either allows or disallows them based on predefined criteria.
	 *
	 * @param array $match An array containing the matched HTML tag, its name, attributes, and content.
	 * @return string The processed string with the matched HTML tag replaced by its content if it passes the filtering rules, or the original tag if it does not.
	 */
	public static function replaceToHtmlSource($match)
	{
		list($target, $name, $attr) = $match;
		$name = strToLower($name);
		$value = end($match);

		if (strpos($value, '<') !== false) {
			return $target;
		}

		if (preg_match('/script|style|link|html|body|frame/', $name)) {
			return $target;
		}

		if ($attr !== '') {
			if (preg_match('/ on|about:|script:|@import|behaviou?r|binding|boundary|cookie|eval|expression|include-source|xmlhttp/i', $attr)) {
				return $target;
			}

			$attr = str_replace('*/', '*/  ', $attr);
			$attr = str_replace('&quot;', '"', $attr);
			$attr = preg_replace('/ {2,}/', ' ', $attr);
			$attr = str_replace('=" ', '="', $attr);
			$attr = str_replace(' "', '"', $attr);
			$attr = preg_replace('/^ [a-z]+/ie', "strToLower('$0');", $attr);
		}

		return "<$name$attr>$value</$name>";
	}

	/** 
	 * Remove all null characters from the input string.
	 * This is useful for sanitizing strings by eliminating potentially harmful null characters that may be used in certain types of attacks or to disrupt string processing.
	 *
	 * @param string $string The input string to be sanitized.
	 * @return string The sanitized string with all null characters removed.
	 */
	public static function nTrim(string $string)
	{
		return str_replace("\x00", '', $string);
	}

	/** 
	 * Replace all occurrences of the HTML line break tag with newline characters in the input string.
	 * This is useful for converting HTML-formatted text into plain text by replacing line breaks with actual newline characters.
	 *
	 * @param string $string The input string containing HTML line break tags.
	 * @return string The processed string with HTML line break tags replaced by newline characters.
	 */
	public static function brToNl(string $string)
	{
		return str_replace('<br />', "\r\n", $string);
	}

	/** 
	 * Replace all newline characters in the input string with HTML line break tags.
	 * This is useful for converting plain text into HTML-formatted text by replacing newline characters with HTML line break tags.
	 *
	 * @param string $string The input string containing newline characters.
	 * @return string The processed string with newline characters replaced by HTML line break tags.
	 */
	public static function nlToBr(string $string)
	{
		return preg_replace('/\r\n?|\n/', '<br />', $string);
	}

	/** 
	 * Convert special characters in a string to their corresponding HTML entities.
	 * This is useful for preventing XSS attacks and ensuring that special characters are displayed correctly in HTML contexts.
	 *
	 * @param string $string The input string to be converted.
	 * @return string The converted string with special characters replaced by HTML entities.
	 */
	public static function stripTags(string $string, array|string|null $tags = '')
	{
		if ($tags === '') {
			return strip_tags($string);
		}

		$tags = str_replace(',', '><', $tags);
		$tags = "<$tags>";

		return strip_tags($string, $tags);
	}
}
