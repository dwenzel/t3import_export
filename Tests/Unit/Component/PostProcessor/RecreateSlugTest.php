<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\PostProcessor;

use CPSIT\T3importExport\Component\PostProcessor\RecreateSlug;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RecreateSlug::class)]
class RecreateSlugTest extends TestCase
{
    protected RecreateSlug $subject;

    protected function setUp(): void
    {
        $this->subject = new RecreateSlug();
    }

    public static function invalidConfigurationDataProvider(): array
    {
        return [
            'table name not a string' => [[RecreateSlug::KEY_TABLE_NAME => 5]],
            'slug field not a string' => [[RecreateSlug::KEY_SLUG_FIELD => ['slug']]],
            'site id not an integer' => [[RecreateSlug::KEY_SITE_ID => 'foo']],
        ];
    }

    #[DataProvider('invalidConfigurationDataProvider')]
    public function testIsConfigurationValidReturnsFalseForInvalidConfiguration(array $configuration): void
    {
        $this->assertFalse($this->subject->isConfigurationValid($configuration));
    }

    public static function validConfigurationDataProvider(): array
    {
        return [
            'empty configuration' => [[]],
            'complete' => [
                [
                    RecreateSlug::KEY_TABLE_NAME => 'pages',
                    RecreateSlug::KEY_SLUG_FIELD => 'slug',
                    RecreateSlug::KEY_SITE_ID => '1',
                ],
            ],
        ];
    }

    #[DataProvider('validConfigurationDataProvider')]
    public function testIsConfigurationValidReturnsTrueForValidConfiguration(array $configuration): void
    {
        $this->assertTrue($this->subject->isConfigurationValid($configuration));
    }
}
