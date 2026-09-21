<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin\API;

use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Data\ObjectHandler;
use Clover\Classes\Data\StringObject;
use function is_object;
use function is_array;
use function count;

class PublicDataResponse
{
    private null|StringObject|string $requestCount;
    private null|StringObject|int|string $statusCode;
    private null|StringObject|string $statusMessage;
    private ?int $totalCount;
    private ?array $stack = [];
    private ?array $data = [];

    public function __construct()
    {
        $this->data = [];
    }

    public function setRequestCount($count)
    {
        $this->requestCount = $count;
    }

    public function setTotalCount($count)
    {
        $this->totalCount = $count;
    }

    public function setStatusMessage($message)
    {
        $this->statusMessage = $message;
    }

    public function setStatusCode($code)
    {
        $this->statusCode = $code;
    }

    public function setStack($stack)
    {
        if ($stack instanceof ArrayObject) {
            $stack = $stack->toPHPObject();
        }

        if (is_object($stack) && !is_array($stack)) {
            $stack = ObjectHandler::objectToArray($stack);
        }

        if (!is_array($stack)) {
            throw new \Exception('Stack is not Arrayable');
        }

        if (count($stack) === 0) {
            //throw new \Exception('Stack is empty');
        }

        $this->stack = $stack;
    }

    public function setData(string $dataTransferObject)
    {
        foreach ($this->stack as $key => $data) {
            if (!class_exists($dataTransferObject)) {
                continue;
            }

            $this->data[] = ($dataTransferObject::from($key, (object) $data));
        }
    }

    public function getResponseCount(): string|StringObject|null
    {
        return $this->requestCount;
    }

    public function getStatusCode(): string|StringObject|null
    {
        return $this->statusCode;
    }

    /**
     * Get a response data
     * @return PublicDataInterface[]
     */
    public function getData(): array|ArrayObject
    {
        return new ArrayObject($this->data);
    }

    public function hasData(): bool
    {
        return count($this->data) > 0;
    }

}
