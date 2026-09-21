<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Enumeration;

use Clover\Framework\Enumeration\Environment;
use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    public function testEnvironmentConstants(): void
    {
        $this->assertSame('allowed_short_open_tag', Environment::ALLOWED_SHORT_OPEN_TAG);
        $this->assertSame('allowed_upload_file', Environment::ALLOWED_UPLOAD_FILE);
        $this->assertSame('built_operation_system', Environment::BUILT_OPERATION_SYSTEM);
        $this->assertSame('display_errors', Environment::DISPLAY_ERRORS);
        $this->assertSame('display_startup_errors', Environment::DISPLAY_STARTUP_ERRORS);
        $this->assertSame('error_reporting_level', Environment::ERROR_REPORTING_LEVEL);
        $this->assertSame('home_path', Environment::HOME_PATH);
        $this->assertSame('hypertext_preprocessor', Environment::HYPERTEXT_PREPROCESSOR);
        $this->assertSame('maximum_integer_size', Environment::MAXIMUM_INTEGER_SIZE);
        $this->assertSame('max_post_size', Environment::MAXIMUM_POST_SIZE);
        $this->assertSame('max_upload_file_size', Environment::MAXIMUM_UPLOAD_FILE_SIZE);
        $this->assertSame('server', Environment::SERVER);
        $this->assertSame('session_use_cookies', Environment::SESSION_USE_COOKIES);
        $this->assertSame('software', Environment::SOFTWARE);
        $this->assertSame('timezone_id', Environment::TIMEZONE_ID);
        $this->assertSame('version', Environment::VERSION);
    }
}
