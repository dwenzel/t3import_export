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
}
