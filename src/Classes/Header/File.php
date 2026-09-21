<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Header;

use Clover\Classes\Header as Header;
use Clover\Enumeration\Encoding as Encoding;
use Clover\Classes\Format\MultiPurposeInternetMailExtensions as MIME;

/**
 * File Header
 */
class File extends Header
{
	/**
	 * Send header with key and array values
	 * 
	 * @param string $header
	 * @param array $pair
	 * 
	 * @return void
	 */
	public static function responseWithKeyAndArray(string $header, array $pair): void
	{
		if (function_exists('create_function')) {
			// @phpstan-ignore-next-line
			array_walk($pair, \create_function('&$i,$k', '$i=" $k=$i;";'));
		} else {
			array_walk($pair, function (&$i, $k) {
				$i = $k . "=" . $i . ";";
			});
		}

		$responseData = implode("", $pair);

		parent::response($header . ";" . $responseData);
	}

	/**
	 * Send header with charset
	 * 
	 * @param string $application
	 * @param Encoding $characterSet
	 * 
	 * @return void
	 */
	public static function responseWithCharset($application, Encoding $characterSet): void
	{
		$characterSet = ['charset' => $characterSet];

		self::responseWithKeyAndArray('Content-Type:' . $application, $characterSet);
	}

	/**
	 * Send header with option
	 * 
	 * @param string $application
	 * @param Encoding $characterSet
	 * 
	 * @return void
	 */
	public static function responseWithOption($application, Encoding $characterSet): void
	{
		if ($characterSet) {
			self::responseWithCharset($application, $characterSet);
		} else {
			self::response('application/zip; charset=UTF-8');
		}
	}

	/**
	 * Set content type from mime
	 * 
	 * @param string $mime
	 * @param Encoding|string $characterSet
	 * 
	 * @return void
	 */
	public static function fromMIME(string $mime, Encoding|string $characterSet = Encoding::UTF_8): void
	{
		self::responseWithOption(MIME::getContentTypeFromExtension($mime), $characterSet);
	}

	/**
	 * Set zip file content type
	 * 
	 * @param Encoding|string $characterSet
	 * 
	 * @return void
	 */
	public static function fileZip(Encoding|string $characterSet = Encoding::UTF_8): void
	{
		self::responseWithOption("application/zip", $characterSet);
	}

	/**
	 * Set plain file content type
	 * 
	 * @param Encoding|string $characterSet
	 * 
	 * @return void
	 */
	public static function filePlain(Encoding|string $characterSet = Encoding::UTF_8): void
	{
		self::responseWithOption("text/plain", $characterSet);
	}

	/**
	 * Set xml file content type
	 * 
	 * @param Encoding|string $characterSet
	 * 
	 * @return void
	 */
	public static function fileXml(Encoding|string $characterSet = Encoding::UTF_8): void
	{
		self::responseWithOption("text/xml", $characterSet);
	}

	/**
	 * Set json file content type
	 * 
	 * @param Encoding|string $characterSet
	 * 
	 * @return void
	 */
	public static function fileJson(Encoding|string $characterSet = Encoding::UTF_8): void
	{
		self::responseWithOption("application/json", $characterSet);
	}

	/**
	 * Set pdf file content type
	 * 
	 * @param Encoding|string $characterSet
	 * 
	 * @return void
	 */
	public static function filePdf(Encoding|string $characterSet = Encoding::UTF_8): void
	{
		self::responseWithOption("application/pdf", $characterSet);
	}

	/**
	 * Set gif file content type
	 * 
	 * @param Encoding|string $characterSet
	 * 
	 * @return void
	 */
	public static function fileGif(Encoding|string $characterSet = Encoding::UTF_8): void
	{
		self::responseWithOption("image/gif", $characterSet);
	}

	/**
	 * Set jpeg file content type
	 * 
	 * @param Encoding|string $characterSet
	 * 
	 * @return void
	 */
	public static function fileJpeg(Encoding|string $characterSet = Encoding::UTF_8): void
	{
		self::responseWithOption("image/jpeg", $characterSet);
	}

	/**
	 * Set jpg file content type
	 * 
	 * @param Encoding|string $characterSet
	 * 
	 * @return void
	 */
	public static function fileJpg(Encoding|string $characterSet = Encoding::UTF_8): void
	{
		self::responseWithOption("image/jpg", $characterSet);
	}

	/**
	 * Set png file content type
	 * 
	 * @param Encoding|string $characterSet
	 * 
	 * @return void
	 */
	public static function filePng(Encoding|string $characterSet = Encoding::UTF_8): void
	{
		self::responseWithOption("image/png", $characterSet);
	}

	/**
	 * Set javascript file content type
	 * 
	 * @param Encoding|string $characterSet
	 * 
	 * @return void
	 */
	public static function fileJavascript(Encoding|string $characterSet = Encoding::UTF_8): void
	{
		self::responseWithOption("text/javascript", $characterSet);
	}
}
