<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Component;

use Clover\Classes\File\Handler;
use Clover\Framework\Enumeration\EntryPoint;
use Dotenv\Parser\Parser;
use PHPUnit\Framework\TestCase;

final class EnvironmentFileTest extends TestCase
{
	public function testEnvironmentExampleUsesValidDotenvSyntax(): void
	{
		$environmentEntries = (new Parser())->parse(Handler::getContent($this->environmentExamplePath()));

		$this->assertNotEmpty($environmentEntries);
	}

	/**
	 * The entry point is chosen by configuration, so `.env.example` has to carry the key and the
	 * value it carries has to be one the enum accepts - otherwise copying the example produces an
	 * application that refuses to start.
	 */
	public function testEnvironmentExampleDocumentsASelectableEntryPoint(): void
	{
		$contents = Handler::getContent($this->environmentExamplePath());

		$this->assertMatchesRegularExpression(
			'/^' . EntryPoint::ENVIRONMENT_KEY . '=(.*)$/m',
			$contents,
			sprintf('`.env.example` must document %s.', EntryPoint::ENVIRONMENT_KEY)
		);

		$matched = preg_match('/^' . EntryPoint::ENVIRONMENT_KEY . '=(.*)$/m', $contents, $matches);

		$this->assertSame(1, $matched);
		$this->assertSame(EntryPoint::default(), EntryPoint::fromString($matches[1]));
	}

	/**
	 * Absolute path of the bundled `.env.example`.
	 *
	 * @return string
	 */
	private function environmentExamplePath(): string
	{
		return dirname(__DIR__, 4)
			. DIRECTORY_SEPARATOR
			. 'root'
			. DIRECTORY_SEPARATOR
			. '.env.example';
	}
}
