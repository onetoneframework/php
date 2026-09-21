<?php

declare(strict_types=1);

namespace Clover\Tests\Message;

use Clover\Message\FileHandler\FileHandlerMessage;
use Clover\Message\Functions\FunctionMessage;
use PHPUnit\Framework\TestCase;

final class MessageTest extends TestCase
{
	public function testFunctionMessageIsStable(): void
	{
		$this->assertSame('Function is not exists', FunctionMessage::getFunctionIsNotFileMessage());
	}

	public function testFileHandlerMessagesAreStable(): void
	{
		$this->assertSame('Target file is not type of File', FileHandlerMessage::getTargetIsNotFileMessage());
		$this->assertSame('Handler type is not a resource', FileHandlerMessage::getInvalidFileHandler());
		$this->assertSame("Don't use the sub-directory syntax", FileHandlerMessage::getDoNotUseSubDirectorySyntaxMessage());
		$this->assertSame("Don't use the `Phar` protocol, it is dangerous", FileHandlerMessage::getDoNotUsePharProtocolMessage());
	}

	public function testMissingFileMessageIncludesExactPath(): void
	{
		$this->assertSame(
			"'C:\\data\\missing.txt' File is not exists or permissions denied",
			FileHandlerMessage::getFileIsNotExistsMessage('C:\\data\\missing.txt')
		);
	}
}
