<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Permission;

use Clover\Classes\Permission;
use Clover\Classes\Permission\Group;
use Clover\Classes\Permission\Owner;
use Clover\Classes\Permission\World;
use PHPUnit\Framework\TestCase;

final class PermissionTest extends TestCase
{
    public function testPermissionFileTypeFlags(): void
    {
        $regular = new Permission(0x0800);
        $directory = new Permission(0x0040);
        $symbolic = new Permission(0xA000);
        $socket = new Permission(0xC000);
        $fifo = new Permission(0x0100);
        $special = new Permission(0x0020);
        $block = new Permission(0x6000);

        $this->assertTrue($regular->isRegular());
        $this->assertFalse($regular->isDirectory());
        $this->assertTrue($directory->isDirectory());
        $this->assertTrue($symbolic->isSymbolicLink());
        $this->assertTrue($socket->isSocket());
        $this->assertTrue($fifo->isFirstInFirstOutPipe());
        $this->assertTrue($special->isSpecialCharacters());
        $this->assertTrue($block->isBlockSpecial());
    }

    public function testOwnerPermissionsAndExecutableSymbol(): void
    {
        new Permission(0x0100 | 0x0008 | 0x0400);
        $owner = new Owner();

        $this->assertTrue($owner->isReadable());
        $this->assertFalse($owner->isWritable());
        $this->assertSame('s', $owner->getExecutableUsers());

        new Permission(0x0400);
        $ownerNoExec = new Owner();
        $this->assertSame('S', $ownerNoExec->getExecutableUsers());
    }

    public function testOwnerWritableBitCurrentBehaviorRegression(): void
    {
        // Note: current implementation compares 0x0080 mask with 0x0100,
        // so owner-write bit alone is treated as false.
        new Permission(0x0080);
        $ownerOnlyWrite = new Owner();
        $this->assertFalse($ownerOnlyWrite->isWritable());

        // And owner-read bit is treated as writable under current logic.
        new Permission(0x0100);
        $ownerOnlyRead = new Owner();
        $this->assertTrue($ownerOnlyRead->isWritable());
    }

    public function testGroupPermissionsAndExecutableSymbol(): void
    {
        new Permission(0x0020 | 0x0010 | 0x0008 | 0x0400);
        $group = new Group();

        $this->assertTrue($group->isReadable());
        $this->assertTrue($group->isWritable());
        $this->assertSame('s', $group->getExecutableUsers());

        new Permission(0x0400);
        $groupNoExec = new Group();
        $this->assertSame('S', $groupNoExec->getExecutableUsers());
    }

    public function testWorldPermissionsAndExecutableSymbol(): void
    {
        new Permission(0x0004 | 0x0002 | 0x0001 | 0x0200);
        $world = new World();

        $this->assertTrue($world->isReadable());
        $this->assertTrue($world->isWritable());
        $this->assertSame('t', $world->getExecutableUsers());

        new Permission(0x0200);
        $worldNoExec = new World();
        $this->assertSame('T', $worldNoExec->getExecutableUsers());
    }
}
