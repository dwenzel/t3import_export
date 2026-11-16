<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

use CPSIT\T3importExport\Component\PreProcessor\ImplodeArray;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Describes the behaviour of ImplodeArray before it moves to the core
 * (CPS-IT/import-export-core#68)
 */
#[CoversClass(ImplodeArray::class)]
class ImplodeArrayTest extends TestCase
{
    protected ImplodeArray $subject;

    protected function setUp(): void
    {
        $this->subject = new ImplodeArray();
    }

    public static function isConfigurationValidDataProvider(): iterable
    {
        yield 'empty configuration' => [[], false];
        yield 'fields not an array' => [['fields' => 'foo'], false];
        yield 'field configuration empty' => [['fields' => ['foo' => []]], false];
        yield 'empty fields' => [['fields' => []], true];
        yield 'field configuration array' => [['fields' => ['foo' => ['wrap' => ';']]], true];
        // accepted, but ignored by process()
        yield 'field configuration string' => [['fields' => ['foo' => 'bar']], true];
    }

    #[Test]
    #[DataProvider('isConfigurationValidDataProvider')]
    public function isConfigurationValidReturnsCorrectValues(array $configuration, bool $expected): void
    {
        self::assertSame($expected, $this->subject->isConfigurationValid($configuration));
    }

    public static function processDataProvider(): iterable
    {
        yield 'array with default glue' => [
            ['foo' => ['wrap' => null]],
            ['foo' => ['a', 'b', 'c']],
            ['foo' => 'a,b,c'],
        ];
        yield 'array with wrap as glue' => [
            ['foo' => ['wrap' => ' | ']],
            ['foo' => ['a', 'b']],
            ['foo' => 'a | b'],
        ];
        yield 'empty array' => [
            ['foo' => ['wrap' => ';']],
            ['foo' => []],
            ['foo' => ''],
        ];
        yield 'keys are dropped' => [
            ['foo' => ['wrap' => ';']],
            ['foo' => ['x' => 'a', 'y' => 'b']],
            ['foo' => 'a;b'],
        ];
        yield 'string value is kept' => [
            ['foo' => ['wrap' => ';']],
            ['foo' => 'a'],
            ['foo' => 'a'],
        ];
        yield 'missing field' => [
            ['foo' => ['wrap' => ';']],
            ['bar' => ['a']],
            ['bar' => ['a']],
        ];
        yield 'child array with default glue' => [
            ['tags' => ['child' => 'tag']],
            ['tags' => ['tag' => ['something', 'anything']]],
            ['tags' => 'something,anything'],
        ];
        yield 'child array with wrap as glue' => [
            ['tags' => ['child' => 'tag', 'wrap' => ', ']],
            ['tags' => ['tag' => ['something', 'anything']]],
            ['tags' => 'something, anything'],
        ];
        yield 'single child string replaces the field' => [
            ['tags' => ['child' => 'tag', 'wrap' => ', ']],
            ['tags' => ['tag' => 'something']],
            ['tags' => 'something'],
        ];
        yield 'missing child is ignored' => [
            ['tags' => ['child' => 'tag']],
            ['tags' => ['other' => ['a']]],
            ['tags' => ['other' => ['a']]],
        ];
        yield 'missing field with child' => [
            ['tags' => ['child' => 'tag']],
            ['bar' => 'a'],
            ['bar' => 'a'],
        ];
        yield 'field configuration string is ignored' => [
            ['foo' => 'bar'],
            ['foo' => ['a', 'b']],
            ['foo' => ['a', 'b']],
        ];
    }

    #[Test]
    #[DataProvider('processDataProvider')]
    public function processImplodesFields(array $fields, array $record, array $expected): void
    {
        self::assertTrue($this->subject->process(['fields' => $fields], $record));
        self::assertSame($expected, $record);
    }

    #[Test]
    public function processImplodesSeveralFields(): void
    {
        $record = ['foo' => ['a', 'b'], 'bar' => ['c', 'd'], 'baz' => ['e']];
        $configuration = [
            'fields' => [
                'foo' => ['wrap' => '-'],
                'bar' => ['wrap' => '+'],
            ],
        ];

        $this->subject->process($configuration, $record);

        self::assertSame(['foo' => 'a-b', 'bar' => 'c+d', 'baz' => ['e']], $record);
    }
}
