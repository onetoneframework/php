<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

use Clover\Classes\Data\EmailObject;
use PHPUnit\Framework\TestCase;

final class EmailObjectTest extends TestCase
{
    public function testExtractsNameAndDomainFromValidEmail(): void
    {
        $email = new EmailObject('user@gmail.com');

        $this->assertSame('user', (string) $email->getName());
        $this->assertSame('gmail.com', (string) $email->getDomain());
        $this->assertSame('user@gmail.com', (string) $email);
    }

    public function testInvalidEmailReturnsEmptyParts(): void
    {
        $email = new EmailObject('not-an-email');

        $this->assertSame('', (string) $email->getName());
        $this->assertSame('', (string) $email->getDomain());
    }

    public function testKnownDomainDetection(): void
    {
        $known = new EmailObject('alice@gmail.com');
        $unknown = new EmailObject('alice@unknown-example.zzz');

        $this->assertTrue($known->isKnownDomain());
        $this->assertFalse($unknown->isKnownDomain());
    }
}
