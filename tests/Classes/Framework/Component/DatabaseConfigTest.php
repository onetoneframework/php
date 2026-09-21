<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Component;

use Clover\Classes\Database\Driver\PHPDataObject;
use Clover\Framework\Component\DatabaseConfig;
use PHPUnit\Framework\TestCase;

final class DatabaseConfigTest extends TestCase
{
    private array $originalEnv = [];

    protected function setUp(): void
    {
        $this->originalEnv = $_ENV;
    }

    protected function tearDown(): void
    {
        $_ENV = $this->originalEnv;
        putenv('MYSQL_HOST');
        putenv('MYSQL_PORT');
        putenv('MYSQL_USERNAME');
        putenv('MYSQL_PASSWORD');
        putenv('MYSQL_DATABASE');
    }

    public function testFromEnvResolvesTrimmedValuesAndNullablePort(): void
    {
        $_ENV['MYSQL_HOST'] = ' db.local ';
        $_ENV['MYSQL_PORT'] = ' 3307 ';
        $_ENV['MYSQL_USERNAME'] = ' root ';
        $_ENV['MYSQL_PASSWORD'] = ' secret ';
        $_ENV['MYSQL_DATABASE'] = ' app_db ';

        $config = DatabaseConfig::fromEnv();

        $this->assertSame('db.local', $config->host);
        $this->assertSame('3307', $config->port);
        $this->assertSame('root', $config->username);
        $this->assertSame('secret', $config->password);
        $this->assertSame('app_db', $config->database);
    }

    public function testFromEnvUsesEmptyDefaultsAndNullPortWhenUnset(): void
    {
        unset($_ENV['MYSQL_HOST'], $_ENV['MYSQL_PORT'], $_ENV['MYSQL_USERNAME'], $_ENV['MYSQL_PASSWORD'], $_ENV['MYSQL_DATABASE']);

        $config = DatabaseConfig::fromEnv();

        $this->assertSame('', $config->host);
        $this->assertNull($config->port);
        $this->assertSame('', $config->username);
        $this->assertSame('', $config->password);
        $this->assertSame('', $config->database);
    }

    public function testApplyPassesAllPropertiesToDatabaseObject(): void
    {
        $config = new DatabaseConfig(
            host: 'db.internal',
            port: '3306',
            username: 'app_user',
            password: 'app_secret',
            database: 'app_database',
        );

        $db = $this->getMockBuilder(PHPDataObject::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setHostName', 'setPort', 'setUsername', 'setPassword', 'setDatabase'])
            ->getMock();

        $db->expects($this->once())->method('setHostName')->with('db.internal');
        $db->expects($this->once())->method('setPort')->with('3306');
        $db->expects($this->once())->method('setUsername')->with('app_user');
        $db->expects($this->once())->method('setPassword')->with('app_secret');
        $db->expects($this->once())->method('setDatabase')->with('app_database');

        $config->apply($db);
    }
}
