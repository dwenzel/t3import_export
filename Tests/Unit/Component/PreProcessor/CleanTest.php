<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

use CPSIT\T3importExport\Component\PreProcessor\Clean;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Describes the behaviour of Clean before it moves to the core
 * (CPS-IT/import-export-core#68)
 */
#[CoversClass(Clean::class)]
class CleanTest extends TestCase
{
    protected Clean $subject;

    protected function setUp(): void
    {
        $this->subject = new Clean();
    }

    public static function isConfigurationValidDataProvider(): iterable
    {
        yield 'empty configuration' => [[], false];
        yield 'fields not an array' => [['fields' => 'foo'], false];
        yield 'field configuration empty' => [['fields' => ['foo' => []]], false];
        yield 'field configuration not an array' => [['fields' => ['foo' => 'trim']], false];
        yield 'empty fields' => [['fields' => []], true];
        yield 'valid' => [['fields' => ['foo' => ['trim' => '1']]], true];
    }

    #[Test]
    #[DataProvider('isConfigurationValidDataProvider')]
    public function isConfigurationValidReturnsCorrectValues(array $configuration, bool $expected): void
    {
        self::assertSame($expected, $this->subject->isConfigurationValid($configuration));
    }

    public static function singleOptionDataProvider(): iterable
    {
        yield 'str_replace' => [
            ['str_replace' => ['search' => 'http://', 'replace' => 'https://']],
            'see http://example.com and http://example.org',
            'see https://example.com and https://example.org',
        ];
        yield 'str_replace without replace is ignored' => [
            ['str_replace' => ['search' => 'http://']],
            'http://example.com',
            'http://example.com',
        ];
        yield 'str_replace with non-string search is ignored' => [
            ['str_replace' => ['search' => ['a'], 'replace' => 'b']],
            'abc',
            'abc',
        ];
        yield 'stripslashes' => [
            ['stripslashes' => '1'],
            'It\\\'s',
            'It\'s',
        ];
        yield 'strip_emptytags' => [
            ['strip_emptytags' => '1'],
            '<p>text</p><p></p><span class="x"> </span><br/>',
            '<p>text</p><br/>',
        ];
        yield 'strip_emptytags keeps whitespace-only strings' => [
            ['strip_emptytags' => '1'],
            '   ',
            '   ',
        ];
        yield 'strip_tags' => [
            ['strip_tags' => '1'],
            '<p>Hello <b>World</b></p>',
            'Hello World',
        ];
        yield 'htmlspecialchars' => [
            ['htmlspecialchars' => '1'],
            '<a href="x">Tom & Jerry\'s</a>',
            '&lt;a href=&quot;x&quot;&gt;Tom &amp; Jerry&#039;s&lt;/a&gt;',
        ];
        yield 'strip_spaces removes all whitespace' => [
            ['strip_spaces' => '1'],
            " a b\tc\nd ",
            'abcd',
        ];
        yield 'trim' => [
            ['trim' => '1'],
            "  foo \n",
            'foo',
        ];
        yield 'ltrim' => [
            ['ltrim' => '1'],
            '  foo  ',
            'foo  ',
        ];
        yield 'rtrim' => [
            ['rtrim' => '1'],
            '  foo  ',
            '  foo',
        ];
        yield 'strtolower' => [
            ['strtolower' => '1'],
            'FoO',
            'foo',
        ];
        yield 'strtoupper' => [
            ['strtoupper' => '1'],
            'FoO',
            'FOO',
        ];
        yield 'option value is not evaluated' => [
            ['trim' => '0'],
            ' foo ',
            'foo',
        ];
    }

    #[Test]
    #[DataProvider('singleOptionDataProvider')]
    public function processAppliesOption(array $fieldConfiguration, string $value, string $expected): void
    {
        $record = ['foo' => $value];

        self::assertTrue($this->subject->process(['fields' => ['foo' => $fieldConfiguration]], $record));
        self::assertSame($expected, $record['foo']);
    }

    /**
     * Options are applied in a fixed order, independent of the order in the configuration:
     * str_replace, stripslashes, strip_emptytags, strip_tags, htmlspecialchars,
     * strip_spaces, trim, ltrim, rtrim, strtolower, strtoupper
     */
    public static function optionOrderDataProvider(): iterable
    {
        yield 'str_replace before strip_tags' => [
            ['strip_tags' => '1', 'str_replace' => ['search' => '[b]', 'replace' => '<b>']],
            '[b]bold',
            'bold',
        ];
        yield 'strip_emptytags before strip_tags' => [
            ['strip_tags' => '1', 'strip_emptytags' => '1'],
            '<p></p>text',
            'text',
        ];
        yield 'strip_tags before htmlspecialchars' => [
            ['htmlspecialchars' => '1', 'strip_tags' => '1'],
            '<b>a & b</b>',
            'a &amp; b',
        ];
        yield 'htmlspecialchars before strip_spaces' => [
            ['strip_spaces' => '1', 'htmlspecialchars' => '1'],
            'a & b',
            'a&amp;b',
        ];
        yield 'strtolower before strtoupper' => [
            ['strtoupper' => '1', 'strtolower' => '1'],
            'FoO',
            'FOO',
        ];
    }

    #[Test]
    #[DataProvider('optionOrderDataProvider')]
    public function processAppliesOptionsInFixedOrder(array $fieldConfiguration, string $value, string $expected): void
    {
        $record = ['foo' => $value];

        $this->subject->process(['fields' => ['foo' => $fieldConfiguration]], $record);

        self::assertSame($expected, $record['foo']);
    }

    #[Test]
    public function processCleansSeveralFields(): void
    {
        $record = ['foo' => ' a ', 'bar' => '<b>b</b>', 'baz' => ' c '];
        $configuration = [
            'fields' => [
                'foo' => ['trim' => '1'],
                'bar' => ['strip_tags' => '1'],
            ],
        ];

        $this->subject->process($configuration, $record);

        self::assertSame(['foo' => 'a', 'bar' => 'b', 'baz' => ' c '], $record);
    }

    public static function untouchedValueDataProvider(): iterable
    {
        yield 'integer' => [['foo' => 5]];
        yield 'array' => [['foo' => [' a ']]];
        yield 'null' => [['foo' => null]];
        yield 'missing field' => [['bar' => ' a ']];
    }

    #[Test]
    #[DataProvider('untouchedValueDataProvider')]
    public function processLeavesNonStringValuesUntouched(array $record): void
    {
        $expected = $record;

        $this->subject->process(['fields' => ['foo' => ['trim' => '1', 'strtoupper' => '1']]], $record);

        self::assertSame($expected, $record);
    }

    #[Test]
    public function processIgnoresFieldConfigurationThatIsNoArray(): void
    {
        $record = ['foo' => ' a '];

        $this->subject->process(['fields' => ['foo' => 'trim']], $record);

        self::assertSame(['foo' => ' a '], $record);
    }
}
