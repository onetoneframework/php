<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\LLM\ChatGPT;

use Clover\Classes\LLM\ChatGPT\Client;
use Clover\Classes\Data\StringObject;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private string $apiKey = 'test-api-key';

    public function testConstructor(): void
    {
        $client = new Client($this->apiKey);
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testConstructorWithStringObject(): void
    {
        $apiKey = new StringObject($this->apiKey);
        $client = new Client($apiKey);
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testAddMessage(): void
    {
        $client = new Client($this->apiKey);

        // Reflection to check private property
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('messages');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $property->setAccessible(true);
        }

        $client->addMessage('user', 'Hello');

        $messages = $property->getValue($client);
        $this->assertCount(1, $messages);
        $this->assertEquals(['role' => 'user', 'content' => 'Hello'], $messages[0]);
    }

    public function testSetSystemPrompt(): void
    {
        $client = new Client($this->apiKey);

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('systemPrompt');

        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $property->setAccessible(true);
        }

        $client->setSystemPrompt('You are a helper.');

        $this->assertEquals('You are a helper.', $property->getValue($client));
    }

    public function testSetTemperature(): void
    {
        $client = new Client($this->apiKey);

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('temperature');

        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $property->setAccessible(true);
        }

        $client->setTemperature(0.5);

        $this->assertEquals(0.5, $property->getValue($client));
    }

    public function testUploadMediaThrowsOnNonExistentFile(): void
    {
        $client = new Client($this->apiKey);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File is not exists');
        $client->uploadMedia('non_existent_file.jpg', 'test.jpg');
    }
}
