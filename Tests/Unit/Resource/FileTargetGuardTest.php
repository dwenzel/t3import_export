<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Resource;

use CPSIT\T3importExport\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Resource\FileTargetGuard;
use PHPUnit\Framework\TestCase;

class FileTargetGuardTest extends TestCase
{
    public static function deniedFileNameDataProvider(): iterable
    {
        foreach (['php', 'php3', 'php5', 'php8', 'phtml', 'pht', 'phar', 'phpsh', 'shtml', 'cgi', 'pl', 'PHP'] as $extension) {
            yield $extension => ['export.' . $extension];
        }
        yield 'php before other extension' => ['export.php.csv'];
        yield 'htaccess' => ['.htaccess'];
        yield 'htpasswd' => ['.htpasswd'];
        yield 'user.ini' => ['.user.ini'];
        yield 'php.ini' => ['php.ini'];
        yield 'web.config' => ['web.config'];
        yield 'empty' => [''];
    }

    /**
     * @test
     * @dataProvider deniedFileNameDataProvider
     */
    public function assertFileNameRejectsExecutableAndServerConfigurationFiles(string $fileName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED);

        (new FileTargetGuard())->assertFileName($fileName);
    }

    /**
     * @test
     */
    public function assertFileNameAcceptsOtherFileTypes(): void
    {
        $this->expectNotToPerformAssertions();

        foreach (['export.csv', 'export.xml', 'image.jpg', 'document.pdf'] as $fileName) {
            (new FileTargetGuard())->assertFileName($fileName);
        }
    }

    /**
     * @test
     */
    public function assertFileNameRespectsFileDenyPattern(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] = '\\.exe$';
        try {
            $subject = new FileTargetGuard();
        } finally {
            unset($GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED);

        $subject->assertFileName('setup.exe');
    }

    public static function unsafeFileNameDataProvider(): iterable
    {
        yield 'alternate data stream' => ['export.php::$DATA'];
        yield 'colon' => ['export.csv:stream'];
        yield 'trailing dot' => ['export.php.'];
        yield 'trailing space' => ['export.php '];
        yield 'csv with trailing dot' => ['export.csv.'];
    }

    /**
     * @test
     * @dataProvider unsafeFileNameDataProvider
     */
    public function assertFileNameRejectsColonsAndTrailingDotsOrSpaces(string $fileName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED);

        (new FileTargetGuard())->assertFileName($fileName);
    }

    public static function weakFileDenyPatternDataProvider(): iterable
    {
        yield 'empty pattern' => [''];
        yield 'other pattern' => ['\\.exe$'];
    }

    /**
     * @test
     * @dataProvider weakFileDenyPatternDataProvider
     */
    public function assertFileNameAppliesDefaultFileDenyPattern(string $pattern): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] = $pattern;
        try {
            $subject = new FileTargetGuard();
        } finally {
            unset($GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED);

        $subject->assertFileName('export.phtml');
    }
}
