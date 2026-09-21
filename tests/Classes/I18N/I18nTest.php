<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\I18N;

use Clover\Classes\I18N\I18n;
use PHPUnit\Framework\TestCase;

class I18nTest extends TestCase
{
    private string $langDir;

    public function testIsInstantiable(): void
    {
        $i18n = new I18n('en');
        $this->assertInstanceOf(I18n::class, $i18n);
    }

    public function testSetLanguage(): void
    {
        $i18n = new I18n('en');
        $this->assertEquals('en', $i18n->getLanguage());

        $i18n->setLanguage('kr'); // Alias test
        $this->assertEquals('ko', $i18n->getLanguage());

        $i18n->setLanguage('jp'); // Alias test
        $this->assertEquals('ja', $i18n->getLanguage());
    }

    public function testFallbackLanguage(): void
    {
        $i18n = new I18n('fr', 'en');
        $this->assertEquals('en', $i18n->getFallbackLanguage());
        
        $i18n->setFallbackLanguage('kr');
        $this->assertEquals('ko', $i18n->getFallbackLanguage());
    }

    public function testMergeTranslationsAndTranslate(): void
    {
        $i18n = new I18n('en');
        $i18n->mergeTranslations([
            'hello' => 'Hello World',
            'greet' => 'Hello {name}!',
            'nested' => [
                'key' => 'Nested Value'
            ]
        ]);

        $this->assertEquals('Hello World', $i18n->translate('hello'));
        $this->assertEquals('Hello World', $i18n->t('hello')); // Alias test
        $this->assertEquals('Hello World', $i18n('hello')); // Magic invoke test

        $this->assertEquals('Hello John!', $i18n->translate('greet', ['name' => 'John']));
        $this->assertEquals('Nested Value', $i18n->translate('nested.key'));
        
        $this->assertTrue($i18n->has('hello'));
        $this->assertFalse($i18n->has('non_existent'));
    }

    public function testMissingKeyHandling(): void
    {
        $i18n = new I18n('en');
        $i18n->enableMissingKeyCollection(true);
        
        $collectedKey = null;
        $i18n->setMissingKeyHandler(function ($key, $lang) use (&$collectedKey) {
            $collectedKey = $key;
        });

        // Test missing key returns the key itself with replacements applied
        $result = $i18n->translate('missing.key', ['test' => '123']);
        $this->assertEquals('missing.key', $result);
        
        $this->assertEquals('missing.key', $collectedKey);
        
        $missingKeys = $i18n->getMissingKeys();
        $this->assertContains('missing.key', $missingKeys);
        
        $i18n->clearMissingKeys();
        $this->assertEmpty($i18n->getMissingKeys());
    }

    public function testPluralRules(): void
    {
        $i18n = new I18n('en');
        $i18n->mergeTranslations([
            'apples' => [
                'one' => 'I have 1 apple.',
                'other' => 'I have {count} apples.'
            ]
        ]);

        $this->assertEquals('I have 1 apple.', $i18n->plural('apples', 1));
        $this->assertEquals('I have 5 apples.', $i18n->plural('apples', 5));
        $this->assertEquals('I have 0 apples.', $i18n->plural('apples', 0));

        // Test Russian plural logic (ends in 1 -> one, 2-4 -> few, else -> many)
        $i18nRu = new I18n('ru');
        $i18nRu->mergeTranslations([
            'cars' => [
                'one' => '{count} машина',
                'few' => '{count} машины',
                'many' => '{count} машин',
                'other' => '{count} машин'
            ]
        ]);

        $this->assertEquals('1 машина', $i18nRu->plural('cars', 1)); // 1
        $this->assertEquals('3 машины', $i18nRu->plural('cars', 3)); // 3
        $this->assertEquals('5 машин', $i18nRu->plural('cars', 5)); // 5
        $this->assertEquals('21 машина', $i18nRu->plural('cars', 21)); // 21
    }

    public function testChoice(): void
    {
        $i18n = new I18n('en');
        $i18n->mergeTranslations([
            'items' => '{0} No items | {1} One item | [2,*] {count} items'
        ]);

        $this->assertEquals('No items', $i18n->choice('items', 0));
        $this->assertEquals('One item', $i18n->choice('items', 1));
        $this->assertEquals('5 items', $i18n->choice('items', 5));
        
        // Test array fallback segment choice
        $i18n->mergeTranslations([
            'simple_items' => 'One box | Many boxes'
        ]);
        
        $this->assertEquals('One box', $i18n->choice('simple_items', 1));
        $this->assertEquals('Many boxes', $i18n->choice('simple_items', 2));
    }

    public function testRtlDirection(): void
    {
        $i18n = new I18n('en');
        $this->assertFalse($i18n->isRtl());
        $this->assertEquals('ltr', $i18n->getDirection());

        $i18n->setLanguage('ar');
        $this->assertTrue($i18n->isRtl());
        $this->assertEquals('rtl', $i18n->getDirection());
        
        $i18n->setLanguage('he');
        $this->assertTrue($i18n->isRtl());
    }

    public function testDetectFromHeader(): void
    {
        $supported = ['en', 'ko', 'ja', 'fr'];
        
        // Exact match
        $lang = I18n::detectFromHeader('ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7', $supported);
        $this->assertEquals('ko', $lang);
        
        // Alias match
        $lang = I18n::detectFromHeader('kr,en;q=0.9', $supported);
        $this->assertEquals('ko', $lang);
        
        // Base match
        $lang = I18n::detectFromHeader('fr-CA,en;q=0.8', $supported);
        $this->assertEquals('fr', $lang);
        
        // Fallback to first supported if no match
        $lang = I18n::detectFromHeader('zh-CN,es;q=0.9', $supported);
        $this->assertEquals('en', $lang);
    }
}
