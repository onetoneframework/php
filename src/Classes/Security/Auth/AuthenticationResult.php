<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Security\Auth;

/**
 * Authentication result class
 */
class AuthenticationResult
{
    private $success;
    private $user;
    private $message;
    private $code;
    private $data;

    public function __construct(bool $success, ?AuthenticatedUser $user = null, string $message = '', int $code = 0, array $data = [])
    {
        $this->success = $success;
        $this->user = $user;
        $this->message = $message;
        $this->code = $code;
        $this->data = $data;
    }

    /**
     * Success flag
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Failure flag
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Authenticated user
     */
    public function getUser(): ?AuthenticatedUser
    {
        return $this->user;
    }

    /**
     * Message
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Code
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Additional data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Create success result
     */
    public static function success(AuthenticatedUser $user, string $message = 'Authentication successful'): self
    {
        return new self(true, $user, $message);
    }

    /**
     * Create failure result
     */
    public static function failure(string $message = 'Authentication failed', int $code = 0, array $data = []): self
    {
        return new self(false, null, $message, $code, $data);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'user' => $this->user?->toArray(),
            'message' => $this->message,
            'code' => $this->code,
            'data' => $this->data
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
