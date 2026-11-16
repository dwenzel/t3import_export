<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Validation\Configuration;

use CPSIT\T3importExport\Validation\Configuration\NotEmptyValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;

#[CoversClass(NotEmptyValidator::class)]
class NotEmptyValidatorTest extends TestCase
{
    public static function withoutPathDataProvider(): iterable
    {
        yield 'empty array' => [[], false];
        yield 'not empty array' => [['foo' => ''], true];
    }

    #[Test]
    #[DataProvider('withoutPathDataProvider')]
    public function isValidChecksWholeConfigurationWithoutPath(array $configuration, bool $expected): void
    {
        self::assertSame($expected, (new NotEmptyValidator())->isValid($configuration));
    }

    public static function withPathDataProvider(): iterable
    {
        yield 'string' => [['foo' => ['bar' => 'baz']], true];
        yield 'empty string' => [['foo' => ['bar' => '']], false];
        yield 'string zero' => [['foo' => ['bar' => '0']], false];
        yield 'integer zero' => [['foo' => ['bar' => 0]], false];
        yield 'null' => [['foo' => ['bar' => null]], false];
        yield 'empty array' => [['foo' => ['bar' => []]], false];
        yield 'array' => [['foo' => ['bar' => ['x']]], true];
    }

    #[Test]
    #[DataProvider('withPathDataProvider')]
    public function isValidChecksValueAtPath(array $configuration, bool $expected): void
    {
        self::assertSame($expected, (new NotEmptyValidator('foo/bar'))->isValid($configuration));
    }

    #[Test]
    public function isValidUsesDelimiter(): void
    {
        $subject = new NotEmptyValidator('foo.bar', '.');

        self::assertTrue($subject->isValid(['foo' => ['bar' => 'baz']]));
    }

    #[Test]
    public function isValidThrowsExceptionForMissingPath(): void
    {
        $this->expectException(MissingArrayPathException::class);

        (new NotEmptyValidator('foo/bar'))->isValid(['foo' => []]);
    }
}
