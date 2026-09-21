<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Hash;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Hash\PasswordHash;
use PHPUnit\Framework\TestCase;

class PasswordHashTest extends TestCase
{

    public function setUp(): void
    {
    }

    public function testDynamic(): void
    {
        $key = 'p@jdio7839!!';

        $hash = PasswordHash::bcryptHash($key);
        $this->assertEquals(PasswordHash::verify($key, $hash), $hash);

        $hash = PasswordHash::argon21Hash($key);
        $this->assertEquals(PasswordHash::verify($key, $hash), $hash);

        $hash = PasswordHash::argon2IdHash($key);
        $this->assertEquals(PasswordHash::verify($key, $hash), $hash);

        $hash = PasswordHash::defaultHash($key);
        $this->assertEquals(PasswordHash::verify($key, $hash), $hash);
    }
}
