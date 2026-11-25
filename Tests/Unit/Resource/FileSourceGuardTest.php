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
    }

    protected function subject(): FileSourceGuard
    {
        return new FileSourceGuard([$this->directory], ['data.example.org', '*.cdn.example.com']);
    }

    #[Test]
    public function resolveFileAcceptsFileInAllowedDirectory(): void
    {
        self::assertSame(
            $this->directory . '/image.jpg',
            $this->subject()->resolveFile($this->directory . '/image.jpg')
        );
    }

    #[Test]
    public function resolveFileAcceptsFileInFileadminByDefault(): void
    {
        self::assertSame(
            Environment::getPublicPath() . '/fileadmin/import/image.jpg',
            (new FileSourceGuard())->resolveFile('fileadmin/import/image.jpg')
        );
    }

    #[Test]
    public function resolveFileAcceptsFileInImportDirectoryOfVarPathByDefault(): void
    {
        self::assertSame(
            Environment::getVarPath() . '/import/image.jpg',
            (new FileSourceGuard())->resolveFile(Environment::getVarPath() . '/import/image.jpg')
        );
    }

    #[Test]
    public function resolveFileUsesConfiguredDirectories(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileSourceDirectories'] = [$this->outsideDirectory];

        self::assertSame(
            $this->outsideDirectory . '/secret.txt',
            (new FileSourceGuard())->resolveFile($this->outsideDirectory . '/secret.txt')
        );
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED);
        (new FileSourceGuard())->resolveFile('fileadmin/import/image.jpg');
    }

    public static function fileOutsideOfAllowedDirectoriesDataProvider(): iterable
    {
        yield 'system file' => ['/etc/passwd'];
        yield 'project file' => [Environment::getProjectPath() . '/composer.json'];
        yield 'var directory' => [Environment::getVarPath() . '/log/typo3.log'];
        yield 'relative path outside of fileadmin' => ['typo3conf/system/settings.php'];
        yield 'scheme relative path' => ['//data.example.org/image.jpg'];
    }

    #[Test]
    #[DataProvider('fileOutsideOfAllowedDirectoriesDataProvider')]
    public function resolveFileRejectsFileOutsideOfAllowedDirectories(string $path): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED);

        (new FileSourceGuard())->resolveFile($path);
    }

    #[Test]
    public function resolveFileRejectsFileOutsideOfConfiguredDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED);

        $this->subject()->resolveFile($this->outsideDirectory . '/secret.txt');
    }

    #[Test]
    public function resolveFileRejectsSymbolicLinkLeadingOutOfAllowedDirectory(): void
    {
        if (!@symlink($this->outsideDirectory . '/secret.txt', $this->directory . '/link.txt')) {
            self::markTestSkipped('Symbolic links cannot be created.');
        }
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED);

        $this->subject()->resolveFile($this->directory . '/link.txt');
    }

    #[Test]
    public function resolveFileAcceptsAllowedDirectoryWhichIsSymbolicLink(): void
    {
        $link = $this->outsideDirectory . '/link';
        if (!@symlink($this->directory, $link)) {
            self::markTestSkipped('Symbolic links cannot be created.');
        }

        self::assertSame($link . '/image.jpg', (new FileSourceGuard([$link], []))->resolveFile($link . '/image.jpg'));
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

    #[Test]
    public function resolveFileRejectsExtensionPathsIfNotAllowed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_PATH_NOT_ALLOWED);

        (new FileSourceGuard(null, null, false))->resolveFile('EXT:t3import_export/Resources/Public/Icons/Extension.svg');
    }

    public static function allowedUrlDataProvider(): iterable
    {
        yield 'http' => ['http://data.example.org/image.jpg'];
        yield 'https' => ['https://data.example.org/image.jpg'];
        yield 'upper case host' => ['https://DATA.example.org/image.jpg'];
        yield 'port' => ['https://data.example.org:8443/image.jpg'];
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
        yield 'internal address' => ['http://127.0.0.1:18081/latest/meta-data/'];
        yield 'host with allowed prefix' => ['https://data.example.org.example.net/image.jpg'];
        yield 'domain of wildcard' => ['https://cdn.example.com/image.jpg'];
        yield 'user information' => ['https://data.example.org@127.0.0.1/image.jpg'];
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
