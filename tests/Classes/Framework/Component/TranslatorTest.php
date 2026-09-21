<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Component;

use Clover\Framework\Component\Translator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class TranslatorTest extends TestCase
{
    protected function tearDown(): void
    {
        $_ENV['APP_LOCALE'] = 'en';
        $_ENV['APP_LANG'] = 'en';
        $_ENV['APP_LANGUAGE'] = 'en';
        $_ENV['APP_FALLBACK_LOCALE'] = 'en';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = '';
        $this->resetTranslator();
    }

    public function testUsesAppLocaleWhenProvided(): void
    {
        $_ENV['APP_LOCALE'] = 'ko';
        $this->resetTranslator();

        $translated = Translator::trans('errors.404', [], 'Not Found');

        $this->assertSame('페이지를 찾을 수 없음', $translated);
    }

    public function testUsesAliasVariableAndNormalizesLocale(): void
    {
        unset($_ENV['APP_LOCALE']);
        $_ENV['APP_LOCALE'] = 'ja';
        $this->resetTranslator();

        $translated = Translator::trans('errors.503', [], 'Service unavailable');

        $this->assertSame('サービス利用不可', $translated);
    }

    public function testDetectAndSetFromAcceptLanguageChoosesBestMatch(): void
    {
        $_ENV['APP_LOCALE'] = 'ko';
        $this->resetTranslator();

        $resolved = Translator::detectAndSetFromAcceptLanguage('ko-KR,ko;q=0.9,en;q=0.7');
        $translated = Translator::trans('errors.404', [], 'Not Found');

        $this->assertSame('ko', $resolved);
        $this->assertSame('페이지를 찾을 수 없음', $translated);
    }

    public function testFallbackDefaultWithReplacementWhenKeyMissing(): void
    {
        $_ENV['APP_LOCALE'] = 'en';
        $this->resetTranslator();

        $translated = Translator::trans('missing.key', ['code' => 2002], 'DB failed: {code}');

        $this->assertSame('DB failed: 2002', $translated);
    }

    public function testReturnsMissingKeyWhenDefaultNotProvided(): void
    {
        $_ENV['APP_LOCALE'] = 'en';
        $this->resetTranslator();

        $translated = Translator::trans('missing.key.without.default');

        $this->assertSame('missing.key.without.default', $translated);
    }

    public function testCliWizardDescriptionIsLocalizedForKorean(): void
    {
        $_ENV['APP_LOCALE'] = 'ko';
        $this->resetTranslator();

        $this->assertStringContainsString('대화형', Translator::trans('cli_wizard.command_description'));
    }

    public function testCliWizardMainPromptResolvesInEnglish(): void
    {
        $_ENV['APP_LOCALE'] = 'en';
        $this->resetTranslator();

        $this->assertSame('Choose a category', Translator::trans('cli_wizard.main_prompt'));
    }

    private function resetTranslator(): void
    {
        $reflection = new ReflectionClass(Translator::class);
        $property = $reflection->getProperty('translator');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $property->setAccessible(true);
        }
        $property->setValue(null, null);
    }
}
