<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Implement;

/**
 * File Object Interface
 *
 * Defines the contract for file object operations.
 * Provides methods for reading, writing, and managing file resources.
 */
interface FileObjectInterface
{

	public function getAcceptExtension(array $extension);

	public function setAcceptExtension(array $extension);

	public function hasWriteContentLength();

	public function closeFileHandle();

	public function seek(int $offset): bool;

	public function hasMode(?string $fileMode = null): bool;

	public function isReadable(?string $fileMode = null): bool;

	public function appendContent($filePath);

	public function isEqualByLine(string $string);

	public function isLocked(): bool;

	public function isWritable(): bool;

	public function removeTemporary();

	public function writeContent(string $content, bool $isLarge = false, int $bufferSize = 1024): bool;

	public function getCurrentSize(): int;

	public function getReadedContent(): string;

	public function isReadedContentValid(): bool;

	public function hasReadedContent();

	public function readAllContent();

	public function readContent(int $fileSize = 0);

	public function printFileData(int $mbSize = 8);

	public function isEnoughFreeSpace(): bool;

	public function successToWriteContent(): bool;

	public function getFilePath(): string;

	public function startHandle();

	public function successToStartHandle(): bool;
}
