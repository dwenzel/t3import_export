<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Resource;

use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Resource\FileSourceGuard;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Exception\UnknownPackageException;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[CoversClass(FileSourceGuard::class)]
class FileSourceGuardTest extends TestCase
{
    protected string $directory = '';
    protected string $outsideDirectory = '';

    protected function setUp(): void
    {
        $this->directory = Environment::getPublicPath() . '/typo3temp/var/tests/file-source-guard-' . uniqid('', true);
        $this->outsideDirectory = Environment::getPublicPath() . '/typo3temp/var/tests/file-source-outside-' . uniqid('', true);
        GeneralUtility::mkdir_deep($this->directory);
        GeneralUtility::mkdir_deep($this->outsideDirectory);
        file_put_contents($this->directory . '/image.jpg', 'image');
        file_put_contents($this->outsideDirectory . '/secret.txt', 'secret');
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileSourceDirectories'],
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts']
        );
        GeneralUtility::rmdir($this->directory, true);
        GeneralUtility::rmdir($this->outsideDirectory, true);
        GeneralUtility::purgeInstances();
    }

    protected function subject(): FileSourceGuard
    {
        return new FileSourceGuard([$this->directory], ['data.example.org', '*.cdn.example.com', 'files.example.org:8443', '127.0.0.1:18081']);
    }

    #[Test]
    public function resolveFileAcceptsFileInAllowedDirectory(): void
    {
        self::assertSame(
            $this->directory . '/image.jpg',
            $this->subject()->resolveFile($this->directory . '/image.jpg', $this->directory . '/')
        );
    }

    #[Test]
    public function resolveFileAcceptsFileInFileadminByDefault(): void
    {
        self::assertSame(
            Environment::getPublicPath() . '/fileadmin/import/image.jpg',
            (new FileSourceGuard())->resolveFile('fileadmin/import/image.jpg', 'fileadmin/import/')
        );
    }

    #[Test]
    public function resolveFileAcceptsFileInImportDirectoryOfVarPathByDefault(): void
    {
        self::assertSame(
            Environment::getVarPath() . '/import/image.jpg',
            (new FileSourceGuard())->resolveFile(Environment::getVarPath() . '/import/image.jpg', Environment::getVarPath() . '/import/')
        );
    }

    #[Test]
    public function resolveFileUsesConfiguredDirectories(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileSourceDirectories'] = [$this->outsideDirectory];

        self::assertSame(
            $this->outsideDirectory . '/secret.txt',
            (new FileSourceGuard())->resolveFile($this->outsideDirectory . '/secret.txt', $this->outsideDirectory . '/')
        );
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED);
        (new FileSourceGuard())->resolveFile('fileadmin/import/image.jpg', 'fileadmin/import/');
    }

    public static function fileOutsideOfAllowedDirectoriesDataProvider(): iterable
    {
        yield 'system file' => ['/etc/passwd', '/etc/'];
        yield 'project file' => [Environment::getProjectPath() . '/composer.json', Environment::getProjectPath() . '/'];
        yield 'var directory' => [Environment::getVarPath() . '/log/typo3.log', Environment::getVarPath() . '/'];
        yield 'relative path outside of fileadmin' => ['typo3conf/system/settings.php', 'typo3conf/'];
        yield 'scheme relative path' => ['//data.example.org/image.jpg', '//data.example.org/'];
    }

    #[Test]
    #[DataProvider('fileOutsideOfAllowedDirectoriesDataProvider')]
    public function resolveFileRejectsFileOutsideOfAllowedDirectories(string $path, string $sourcePath): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED);

        (new FileSourceGuard())->resolveFile($path, $sourcePath);
    }

    #[Test]
    public function resolveFileRejectsFileOutsideOfConfiguredDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED);

        $this->subject()->resolveFile($this->outsideDirectory . '/secret.txt', $this->outsideDirectory . '/');
    }

    #[Test]
    public function resolveFileRejectsSymbolicLinkLeadingOutOfAllowedDirectory(): void
    {
        if (!@symlink($this->outsideDirectory . '/secret.txt', $this->directory . '/link.txt')) {
            self::markTestSkipped('Symbolic links cannot be created.');
        }
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED);

        $this->subject()->resolveFile($this->directory . '/link.txt', $this->directory . '/');
    }

    #[Test]
    public function resolveFileAcceptsAllowedDirectoryWhichIsSymbolicLink(): void
    {
        $link = $this->outsideDirectory . '/link';
        if (!@symlink($this->directory, $link)) {
            self::markTestSkipped('Symbolic links cannot be created.');
        }

        self::assertSame(
            realpath($this->directory) . '/image.jpg',
            (new FileSourceGuard([$link], []))->resolveFile($link . '/image.jpg', $link . '/')
        );
    }

    public static function deniedPathDataProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'dot dot segment' => ['fileadmin/import/../../.env'];
        yield 'leading dot dot' => ['../.env'];
        yield 'backslash dot dot' => ['fileadmin\\..\\..\\.env'];
        yield 'php filter' => ['php://filter/read=convert.base64-encode/resource=fileadmin/image.jpg'];
        yield 'compress.zlib' => ['compress.zlib://fileadmin/image.jpg'];
        yield 'phar' => ['phar://fileadmin/image.jpg'];
        yield 'phar without slashes' => ['phar:fileadmin/image.jpg'];
        yield 'data' => ['data:text/plain;base64,SGVsbG8='];
        yield 'file' => ['file:///etc/passwd'];
        yield 'ftp' => ['ftp://data.example.org/image.jpg'];
        yield 'null byte' => ["fileadmin/image.jpg\0.txt"];
        yield 'line break' => ["fileadmin/image\n.jpg"];
    }

    #[Test]
    #[DataProvider('deniedPathDataProvider')]
    public function resolveFileRejectsPath(string $path): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_PATH_NOT_ALLOWED);

        (new FileSourceGuard())->resolveFile($path);
    }

    /**
     * The extension `my_ext` is in `$this->directory`, with the files
     * `image.jpg`, `Import/a.csv` and `Import/prefix_a.csv`.
     */
    protected function mockExtension(): void
    {
        GeneralUtility::mkdir_deep($this->directory . '/Import');
        file_put_contents($this->directory . '/Import/a.csv', 'a');
        file_put_contents($this->directory . '/Import/prefix_a.csv', 'a');
        $package = $this->createMock(PackageInterface::class);
        $package->method('getPackagePath')->willReturn($this->directory . '/');
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('extractPackageKeyFromPackagePath')->willReturnCallback(
            static fn(string $path): string => explode('/', substr($path, 4))[0]
        );
        $packageManager->method('getPackage')->willReturnCallback(
            static function (string $key) use ($package): PackageInterface {
                if ($key !== 'my_ext') {
                    throw new UnknownPackageException('unknown', 1795400299);
                }

                return $package;
            }
        );
        $packageManager->method('isPackageActive')->willReturnCallback(static fn(string $key): bool => $key === 'my_ext');
        $packageManager->method('resolvePackagePath')->willReturnCallback(
            function (string $path): string {
                if ($path === 'EXT:my_ext') {
                    return $this->directory;
                }
                if (!str_starts_with($path, 'EXT:my_ext/')) {
                    throw new UnknownPackageException('unknown', 1795400299);
                }

                return $this->directory . '/' . substr($path, \strlen('EXT:my_ext/'));
            }
        );
        ExtensionManagementUtility::setPackageManager($packageManager);
        GeneralUtility::setSingletonInstance(PackageManager::class, $packageManager);
    }

    public static function extensionFileDataProvider(): iterable
    {
        yield 'directory' => ['EXT:my_ext/Import/', 'a.csv', '/Import/a.csv'];
        yield 'prefix of file name' => ['EXT:my_ext/Import/prefix_', 'a.csv', '/Import/prefix_a.csv'];
    }

    #[Test]
    #[DataProvider('extensionFileDataProvider')]
    public function resolveFileAcceptsFileBelowExtensionSourcePath(string $sourcePath, string $value, string $expected): void
    {
        $this->mockExtension();
        $guard = new FileSourceGuard([$this->outsideDirectory], []);

        self::assertSame(
            realpath($this->directory) . $expected,
            $guard->resolveFile($guard->buildLocation($sourcePath, $value), $sourcePath)
        );
    }

    public static function deniedExtensionPathDataProvider(): iterable
    {
        yield 'value without source path' => [null, 'EXT:my_ext/Import/a.csv', FileSourceGuard::ERROR_PATH_NOT_ALLOWED];
        yield 'other extension as value' => [null, 'EXT:core/composer.json', FileSourceGuard::ERROR_PATH_NOT_ALLOWED];
        yield 'dot dot segment' => ['EXT:my_ext/Import/', '../image.jpg', FileSourceGuard::ERROR_PATH_NOT_ALLOWED];
        yield 'local source path' => ['fileadmin/', 'EXT:my_ext/image.jpg', FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED];
        yield 'unknown extension' => ['EXT:unknown/Import/', 'a.csv', FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED];
    }

    #[Test]
    #[DataProvider('deniedExtensionPathDataProvider')]
    public function resolveFileRejectsExtensionPaths(?string $sourcePath, string $value, int $code): void
    {
        $this->mockExtension();
        $guard = new FileSourceGuard([$this->outsideDirectory], []);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode($code);

        $guard->resolveFile($guard->buildLocation($sourcePath, $value), $sourcePath);
    }

    #[Test]
    public function resolveFileRejectsExtensionPathWhichIsNotBelowSourcePath(): void
    {
        $this->mockExtension();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_PATH_NOT_ALLOWED);

        (new FileSourceGuard([], []))->resolveFile('EXT:my_ext/image.jpg', 'EXT:my_ext/Import/');
    }

    #[Test]
    public function resolveFileUsesExistingDirectoryOfExtensionSourcePathWithoutTrailingSlash(): void
    {
        $this->mockExtension();

        self::assertSame(
            realpath($this->directory) . '/Import/a.csv',
            (new FileSourceGuard([], []))->resolveFile('EXT:my_ext/Import/a.csv', 'EXT:my_ext')
        );
    }

    #[Test]
    public function resolveFileRejectsSymbolicLinkLeadingOutOfSourcePath(): void
    {
        $this->mockExtension();
        if (!@symlink($this->directory . '/image.jpg', $this->directory . '/Import/link.jpg')) {
            self::markTestSkipped('Symbolic links cannot be created.');
        }
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED);

        (new FileSourceGuard([], []))->resolveFile('EXT:my_ext/Import/link.jpg', 'EXT:my_ext/Import/');
    }

    #[Test]
    public function resolveFileRejectsSourcePathLeadingOutOfExtension(): void
    {
        $this->mockExtension();
        file_put_contents($this->outsideDirectory . '/a.csv', 'a');
        if (!@symlink($this->outsideDirectory, $this->directory . '/Link')) {
            self::markTestSkipped('Symbolic links cannot be created.');
        }
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED);

        (new FileSourceGuard([], []))->resolveFile('EXT:my_ext/Link/a.csv', 'EXT:my_ext/Link/');
    }

    /**
     * `$this->directory` with the files `image.jpg`, `Import/a.csv`,
     * `Import/prefix_a.csv` and `Importer/b.csv`
     */
    protected function createSourceFiles(): void
    {
        GeneralUtility::mkdir_deep($this->directory . '/Import');
        GeneralUtility::mkdir_deep($this->directory . '/Importer');
        file_put_contents($this->directory . '/Import/a.csv', 'a');
        file_put_contents($this->directory . '/Import/prefix_a.csv', 'a');
        file_put_contents($this->directory . '/Importer/b.csv', 'b');
    }

    public static function missingSourcePathDataProvider(): iterable
    {
        yield 'no source path' => [null];
        yield 'empty source path' => [''];
        yield 'URL source path' => ['https://data.example.org/'];
    }

    #[Test]
    #[DataProvider('missingSourcePathDataProvider')]
    public function resolveFileRejectsLocalFileWithoutLocalSourcePath(?string $sourcePath): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_PATH_NOT_ALLOWED);

        $this->subject()->resolveFile($this->directory . '/image.jpg', $sourcePath);
    }

    #[Test]
    public function resolveFileRejectsPathWhichDoesNotStartWithSourcePath(): void
    {
        $this->createSourceFiles();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_PATH_NOT_ALLOWED);

        $this->subject()->resolveFile($this->directory . '/image.jpg', $this->directory . '/Import/');
    }

    public static function localSourceFileDataProvider(): iterable
    {
        yield 'directory with trailing slash' => ['/Import/', 'a.csv', '/Import/a.csv'];
        yield 'existing directory without trailing slash' => ['/Import', '/a.csv', '/Import/a.csv'];
        yield 'prefix of file name' => ['/Import/prefix_', 'a.csv', '/Import/prefix_a.csv'];
        yield 'subdirectory' => ['/', 'Import/a.csv', '/Import/a.csv'];
    }

    #[Test]
    #[DataProvider('localSourceFileDataProvider')]
    public function resolveFileAcceptsFileBelowLocalSourcePath(string $sourcePath, string $value, string $expected): void
    {
        $this->createSourceFiles();
        $guard = $this->subject();
        $sourcePath = $this->directory . $sourcePath;

        self::assertSame(
            realpath($this->directory) . $expected,
            $guard->resolveFile($guard->buildLocation($sourcePath, $value), $sourcePath)
        );
    }

    public static function fileOutsideOfLocalSourcePathDataProvider(): iterable
    {
        yield 'sibling of existing directory without trailing slash' => ['/Import', 'er/b.csv'];
        yield 'file next to existing directory without trailing slash' => ['/Import', '.csv'];
    }

    #[Test]
    #[DataProvider('fileOutsideOfLocalSourcePathDataProvider')]
    public function resolveFileRejectsFileOutsideOfLocalSourceDirectory(string $sourcePath, string $value): void
    {
        $this->createSourceFiles();
        file_put_contents($this->directory . '/Import.csv', 'c');
        $guard = $this->subject();
        $sourcePath = $this->directory . $sourcePath;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED);

        $guard->resolveFile($guard->buildLocation($sourcePath, $value), $sourcePath);
    }

    #[Test]
    public function resolveFileUsesParentDirectoryOfFileNamePrefixAsBound(): void
    {
        $this->createSourceFiles();
        if (!@symlink($this->directory . '/image.jpg', $this->directory . '/Import/prefix_link.jpg')) {
            self::markTestSkipped('Symbolic links cannot be created.');
        }
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED);

        // the bound of "Import/prefix_" is "Import/", the link leads to its parent
        $this->subject()->resolveFile($this->directory . '/Import/prefix_link.jpg', $this->directory . '/Import/prefix_');
    }

    #[Test]
    public function resolveFileRejectsSymbolicLinkLeadingOutOfLocalSourceDirectory(): void
    {
        $this->createSourceFiles();
        if (!@symlink($this->directory . '/image.jpg', $this->directory . '/Import/link.jpg')) {
            self::markTestSkipped('Symbolic links cannot be created.');
        }
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED);

        $this->subject()->resolveFile($this->directory . '/Import/link.jpg', $this->directory . '/Import/');
    }

    public static function allowedUrlDataProvider(): iterable
    {
        yield 'http' => ['http://data.example.org/image.jpg'];
        yield 'https' => ['https://data.example.org/image.jpg'];
        yield 'upper case host' => ['https://DATA.example.org/image.jpg'];
        yield 'default port of https' => ['https://data.example.org:443/image.jpg'];
        yield 'default port of http' => ['http://data.example.org:80/image.jpg'];
        yield 'configured port' => ['https://files.example.org:8443/image.jpg'];
        yield 'configured port with http' => ['http://files.example.org:8443/image.jpg'];
        yield 'configured port of address' => ['http://127.0.0.1:18081/image.jpg'];
        yield 'sub domain' => ['https://images.cdn.example.com/image.jpg'];
    }

    #[Test]
    #[DataProvider('allowedUrlDataProvider')]
    public function assertUrlAllowedAcceptsUrlOfAllowedHost(string $url): void
    {
        $this->subject()->assertUrlAllowed($url);
        $this->addToAssertionCount(1);
    }

    public static function urlOfDeniedHostDataProvider(): iterable
    {
        yield 'other host' => ['https://example.org/image.jpg'];
        yield 'internal address' => ['http://169.254.169.254/latest/meta-data/'];
        yield 'host with allowed prefix' => ['https://data.example.org.example.net/image.jpg'];
        yield 'domain of wildcard' => ['https://cdn.example.com/image.jpg'];
        yield 'user information' => ['https://data.example.org@127.0.0.1/image.jpg'];
        yield 'port of host without port' => ['https://data.example.org:8443/image.jpg'];
        yield 'http port with https' => ['https://data.example.org:80/image.jpg'];
        yield 'other port of host with port' => ['http://127.0.0.1:18082/image.jpg'];
        yield 'default port of host with port' => ['https://files.example.org/image.jpg'];
    }

    #[Test]
    #[DataProvider('urlOfDeniedHostDataProvider')]
    public function assertUrlAllowedRejectsUrlOfOtherHost(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_HOST_NOT_ALLOWED);

        $this->subject()->assertUrlAllowed($url);
    }

    public static function deniedUrlDataProvider(): iterable
    {
        yield 'ftp' => ['ftp://data.example.org/image.jpg'];
        yield 'file' => ['file:///etc/passwd'];
        yield 'php' => ['php://filter/resource=https://data.example.org/'];
        yield 'no host' => ['https:///image.jpg'];
        yield 'space' => ['https://data.example.org/image .jpg'];
        yield 'backslash' => ['https://127.0.0.1\\@data.example.org/image.jpg'];
    }

    #[Test]
    #[DataProvider('deniedUrlDataProvider')]
    public function assertUrlAllowedRejectsUrl(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_URL_NOT_ALLOWED);

        $this->subject()->assertUrlAllowed($url);
    }

    #[Test]
    public function noHostIsAllowedByDefault(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_HOST_NOT_ALLOWED);

        (new FileSourceGuard())->assertUrlAllowed('https://data.example.org/image.jpg');
    }

    #[Test]
    public function allowedHostsCanBeConfigured(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts'] = ['data.example.org'];

        $guard = new FileSourceGuard();

        self::assertTrue($guard->isHostAllowed('https://data.example.org/image.jpg'));
        self::assertFalse($guard->isHostAllowed('https://example.org/image.jpg'));
    }

    #[Test]
    public function asteriskAllowsAllHosts(): void
    {
        self::assertTrue((new FileSourceGuard([], ['*']))->isHostAllowed('https://example.org/image.jpg'));
    }

    #[Test]
    public function buildLocationPrefixesLocalSourcePath(): void
    {
        self::assertSame('fileadmin/import/image.jpg', $this->subject()->buildLocation('fileadmin/import/', 'image.jpg'));
        self::assertSame('image.jpg', $this->subject()->buildLocation(null, 'image.jpg'));
    }

    #[Test]
    public function buildLocationPrefixesUrlSourcePath(): void
    {
        self::assertSame(
            'https://data.example.org/images/image.jpg',
            $this->subject()->buildLocation('https://data.example.org/images/', 'image.jpg')
        );
        self::assertSame(
            'https://data.example.org/image.jpg',
            $this->subject()->buildLocation('https://data.example.org', '/image.jpg')
        );
    }

    public static function hostChangingValueDataProvider(): iterable
    {
        yield 'user information' => ['https://data.example.org', '@127.0.0.1:18081/x.txt'];
        yield 'user information of allowed host' => ['https://data.example.org', '@images.cdn.example.com/x.txt'];
        yield 'port' => ['https://data.example.org', ':8443/x.txt'];
        yield 'host suffix' => ['https://images', '.cdn.example.com/x.txt'];
    }

    #[Test]
    #[DataProvider('hostChangingValueDataProvider')]
    public function buildLocationRejectsValueWhichChangesTheHost(string $sourcePath, string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->subject()->buildLocation($sourcePath, $value);
    }
}
