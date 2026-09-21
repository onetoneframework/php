<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Context;

use Clover\Framework\Context\ApplicationContext;
use Clover\Classes\DependencyInjection\Container;
use Clover\Abstract\Interceptor;
use PHPUnit\Framework\TestCase;

class ApplicationContextTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up static properties after each test
        ApplicationContext::clearEnvironment();
        ApplicationContext::clearInterceptors();
        // Container can't be easily cleared if there's no clear method, but we can set it to a new one if needed
    }

    public function testEnvironmentMethods(): void
    {
        $env = ['FOO' => 'bar', 'APP_ENV' => 'testing'];
        
        ApplicationContext::setEnvironment($env);
        $this->assertSame($env, ApplicationContext::getEnvironment());
        
        ApplicationContext::clearEnvironment();
        $this->assertSame([], ApplicationContext::getEnvironment());
    }

    public function testContainerMethods(): void
    {
        $container = new Container();
        
        ApplicationContext::setContainer($container);
        $this->assertSame($container, ApplicationContext::getContainer());
    }

    public function testContainerCanBeReplaced(): void
    {
        $first = new Container();
        $second = new Container();

        ApplicationContext::setContainer($first);
        ApplicationContext::setContainer($second);

        $this->assertSame($second, ApplicationContext::getContainer());
    }

    public function testInterceptorMethods(): void
    {
        $interceptor1 = $this->createMock(Interceptor::class);
        $interceptor2 = $this->createMock(Interceptor::class);
        
        ApplicationContext::setInterceptors([$interceptor1]);
        $this->assertSame([$interceptor1], ApplicationContext::getInterceptors());
        
        // setInterceptors runs array_merge
        ApplicationContext::setInterceptors([$interceptor2]);
        $this->assertSame([$interceptor1, $interceptor2], ApplicationContext::getInterceptors());
        
        ApplicationContext::clearInterceptors();
        $this->assertSame([], ApplicationContext::getInterceptors());
    }

    public function testClearEnvironmentIsIdempotent(): void
    {
        ApplicationContext::setEnvironment(['APP_ENV' => 'testing']);
        ApplicationContext::clearEnvironment();
        ApplicationContext::clearEnvironment();

        $this->assertSame([], ApplicationContext::getEnvironment());
    }

	public function testSetEnvironmentReplacesPreviousSnapshot(): void
	{
		ApplicationContext::setEnvironment([
			'APP_ENV' => 'development',
			'APP_DEBUG' => true,
		]);

		ApplicationContext::setEnvironment(['APP_ENV' => 'production']);

		$this->assertSame(['APP_ENV' => 'production'], ApplicationContext::getEnvironment());
	}
}
