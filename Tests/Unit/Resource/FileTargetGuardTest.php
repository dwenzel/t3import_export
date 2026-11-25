<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Resource;

use CPSIT\T3importExport\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Resource\FileTargetGuard;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileTargetGuardTest extends TestCase
{
    protected string $directory = '';
    protected string $outsideDirectory = '';

    protected function setUp(): void
    {
        $this->directory = 'typo3temp/var/tests/file-target-guard-' . uniqid('', true);
        $this->outsideDirectory = Environment::getPublicPath() . '/file-target-guard-' . uniqid('', true);
        GeneralUtility::mkdir_deep(GeneralUtility::getFileAbsFileName($this->directory));
        GeneralUtility::mkdir_deep($this->outsideDirectory);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileTargetExtensions']);
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileTargetDirectories']);
        GeneralUtility::rmdir(GeneralUtility::getFileAbsFileName($this->directory), true);
        GeneralUtility::rmdir($this->outsideDirectory, true);
    }

    public static function allowedDataFileDataProvider(): iterable
    {
        foreach (FileTargetGuard::DEFAULT_EXTENSIONS as $extension) {
            yield $extension => ['export.' . $extension];
        }
        yield 'upper case extension' => ['export.CSV'];
        yield 'sub directory' => ['sub/export.xml'];
    }

    /**
     * @test
     * @dataProvider allowedDataFileDataProvider
     */
    public function resolveDataFileReturnsAbsolutePathOfAllowedFile(string $fileName): void
    {
        $path = $this->directory . '/' . $fileName;

        self::assertSame(
            GeneralUtility::getFileAbsFileName($path),
            (new FileTargetGuard())->resolveDataFile($path)
        );
    }

    /**
     * @test
     */
    public function resolveDataFileAcceptsFileInFileadmin(): void
    {
        self::assertSame(
            Environment::getPublicPath() . '/fileadmin/export/export.csv',
            (new FileTargetGuard())->resolveDataFile('fileadmin/export/export.csv')
        );
    }

    /**
     * @test
     */
    public function resolveDataFileAcceptsAbsolutePathInAllowedDirectory(): void
    {
        $path = GeneralUtility::getFileAbsFileName($this->directory . '/export.csv');

        self::assertSame($path, (new FileTargetGuard())->resolveDataFile($path));
    }

    public static function executableFileNameDataProvider(): iterable
    {
        foreach (['php', 'php3', 'php5', 'php8', 'phtml', 'pht', 'phar', 'phpsh', 'shtml', 'cgi', 'pl', 'PHP'] as $extension) {
            yield $extension => ['export.' . $extension];
        }
        yield 'php before allowed extension' => ['export.php.csv'];
        yield 'htaccess' => ['.htaccess'];
        yield 'htpasswd' => ['.htpasswd'];
        yield 'user.ini' => ['.user.ini'];
        yield 'php.ini' => ['php.ini'];
        yield 'web.config' => ['web.config'];
    }

    public static function deniedFileNameDataProvider(): iterable
    {
        yield from self::executableFileNameDataProvider();
        foreach (['inc', 'html', 'js', 'svg', 'ini'] as $extension) {
            yield $extension => ['export.' . $extension];
        }
        yield 'no extension' => ['export'];
    }

    /**
     * @test
     * @dataProvider deniedFileNameDataProvider
     */
    public function resolveDataFileRejectsFileTypes(string $fileName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED);

        (new FileTargetGuard())->resolveDataFile($this->directory . '/' . $fileName);
    }

    public static function deniedPathDataProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'dot dot segment' => ['typo3temp/var/tests/../../export.csv'];
        yield 'leading dot dot' => ['../export.csv'];
        yield 'stream wrapper' => ['phar://typo3temp/var/tests/export.csv'];
        yield 'php stream' => ['php://filter/resource=typo3temp/export.csv'];
        yield 'file stream' => ['file:///tmp/export.csv'];
        yield 'extension path' => ['EXT:t3import_export/export.csv'];
        yield 'null byte' => ["typo3temp/export.csv\0.php"];
        yield 'outside of the project' => ['/tmp/export.csv'];
    }

    /**
     * @test
     * @dataProvider deniedPathDataProvider
     */
    public function resolveDataFileRejectsPaths(string $path): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileTargetGuard::ERROR_PATH_NOT_ALLOWED);

        (new FileTargetGuard())->resolveDataFile($path);
    }

    public static function deniedDirectoryDataProvider(): iterable
    {
        yield 'public directory' => ['export.csv'];
        yield 'other directory of the public directory' => ['uploads/export.csv'];
    }

    /**
     * @test
     * @dataProvider deniedDirectoryDataProvider
     */
    public function resolveDataFileRejectsFilesOutsideOfAllowedDirectories(string $path): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileTargetGuard::ERROR_DIRECTORY_NOT_ALLOWED);

        (new FileTargetGuard())->resolveDataFile($path);
    }

    /**
     * @test
     */
    public function resolveDataFileRejectsLinkedDirectoryLeadingOutOfAllowedDirectories(): void
    {
        $link = GeneralUtility::getFileAbsFileName($this->directory . '/link');
        symlink($this->outsideDirectory, $link);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileTargetGuard::ERROR_DIRECTORY_NOT_ALLOWED);

        (new FileTargetGuard())->resolveDataFile($this->directory . '/link/export.csv');
    }

    /**
     * @test
     */
    public function resolveDataFileRejectsLinkToFileWithDeniedType(): void
    {
        $target = GeneralUtility::getFileAbsFileName($this->directory . '/export.php');
        touch($target);
        symlink($target, GeneralUtility::getFileAbsFileName($this->directory . '/export.csv'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED);

        (new FileTargetGuard())->resolveDataFile($this->directory . '/export.csv');
    }

    /**
     * @test
     */
    public function resolveDataFileRejectsDanglingLink(): void
    {
        symlink($this->outsideDirectory . '/missing.csv', GeneralUtility::getFileAbsFileName($this->directory . '/export.csv'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileTargetGuard::ERROR_DIRECTORY_NOT_ALLOWED);

        (new FileTargetGuard())->resolveDataFile($this->directory . '/export.csv');
    }

    /**
     * @test
     */
    public function resolveDataFileAcceptsAllowedDirectoryWhichIsALink(): void
    {
        $link = Environment::getPublicPath() . '/file-target-guard-link-' . uniqid('', true);
        symlink($this->outsideDirectory, $link);
        try {
            $subject = new FileTargetGuard(null, [basename($link)]);

            self::assertSame(
                $link . '/export.csv',
                $subject->resolveDataFile(basename($link) . '/export.csv')
            );
        } finally {
            unlink($link);
        }
    }

    /**
     * @test
     */
    public function extensionsAndDirectoriesCanBeConfigured(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileTargetExtensions'] = ['ics'];
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileTargetDirectories'] = [basename($this->outsideDirectory)];
        $subject = new FileTargetGuard();

        self::assertSame(
            $this->outsideDirectory . '/export.ics',
            $subject->resolveDataFile(basename($this->outsideDirectory) . '/export.ics')
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileTargetGuard::ERROR_DIRECTORY_NOT_ALLOWED);
        $subject->resolveDataFile($this->directory . '/export.ics');
    }

    /**
     * @test
     */
    public function configuredExtensionsDoNotAllowDeniedFileNames(): void
    {
        $subject = new FileTargetGuard(['csv', 'php']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED);

        $subject->resolveDataFile($this->directory . '/export.php');
    }

    /**
     * @test
     */
    public function extensionsCanBeConfiguredAsCommaSeparatedList(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileTargetExtensions'] = 'ics, .vcf';
        $subject = new FileTargetGuard();

        $subject->assertDataFileName('export.ics');
        $subject->assertDataFileName('export.vcf');

        $this->expectException(InvalidArgumentException::class);
        $subject->assertDataFileName('export.csv');
    }

    /**
     * @test
     */
    public function assertFileNameAcceptsOtherFileTypes(): void
    {
        $this->expectNotToPerformAssertions();

        (new FileTargetGuard())->assertFileName('image.jpg');
        (new FileTargetGuard())->assertFileName('document.pdf');
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

    /**
     * @test
     * @dataProvider executableFileNameDataProvider
     */
    public function assertFileNameRejectsExecutableAndServerConfigurationFiles(string $fileName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED);

        (new FileTargetGuard())->assertFileName($fileName);
    }
}
