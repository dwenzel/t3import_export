<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Resource;

use CPSIT\T3importExport\Resource\Typo3PathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Core\Environment;

#[CoversClass(Typo3PathResolver::class)]
class Typo3PathResolverTest extends TestCase
{
    #[Test]
    public function resolvesRelativePathsInThePublicDirectory(): void
    {
        self::assertSame(
            Environment::getPublicPath() . '/fileadmin/import/news.csv',
            (new Typo3PathResolver())->resolve('fileadmin/import/news.csv')
        );
    }

    #[Test]
    public function resolvesAbsolutePathsInTheProjectDirectory(): void
    {
        $path = Environment::getProjectPath() . '/var/import/news.csv';

        self::assertSame($path, (new Typo3PathResolver())->resolve($path));
    }

    public static function notAllowedPathDataProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'null byte' => ["fileadmin/news.csv\0.txt"];
        yield 'parent directory' => ['fileadmin/../../news.csv'];
        yield 'absolute path outside of the project' => ['/etc/passwd'];
        yield 'stream wrapper' => ['php://filter/resource=/etc/passwd'];
        yield 'url' => ['https://example.org/news.csv'];
    }

    #[Test]
    #[DataProvider('notAllowedPathDataProvider')]
    public function rejectsPathsThatAreNotAllowed(string $path): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(Typo3PathResolver::ERROR_NOT_ALLOWED);

        (new Typo3PathResolver())->resolve($path);
    }
}
