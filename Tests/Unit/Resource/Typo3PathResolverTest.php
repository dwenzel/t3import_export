<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Resource;

use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Resource\Typo3PathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Core\Environment;

#[CoversClass(Typo3PathResolver::class)]
class Typo3PathResolverTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $configuration = [];

    private string $testDirectory = '';

    protected function setUp(): void
    {
        $this->configuration = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
    }

    protected function tearDown(): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = $this->configuration;
        if ($this->testDirectory !== '') {
            self::removeDirectory($this->testDirectory);
        }
    }

    #[Test]
    public function allowsFileadminAndImportDirectoryByDefault(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileDirectories']);
        $subject = new Typo3PathResolver();

        self::assertSame(
            [Environment::getPublicPath() . '/fileadmin', Environment::getVarPath() . '/import'],
            $subject->getDirectories()
        );
        self::assertSame(
            Environment::getPublicPath() . '/fileadmin/import/news.csv',
            $subject->resolve('fileadmin/import/news.csv')
        );
        $path = Environment::getVarPath() . '/import/news.csv';
        self::assertSame($path, $subject->resolve($path));
    }

    #[Test]
    public function usesConfiguredDirectories(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileDirectories'] = ['fileadmin/import/', 42];
        $subject = new Typo3PathResolver();

        self::assertSame([Environment::getPublicPath() . '/fileadmin/import'], $subject->getDirectories());
        self::assertSame(
            Environment::getPublicPath() . '/fileadmin/import/news.csv',
            $subject->resolve('fileadmin/import/news.csv')
        );
        $this->expectExceptionCode(Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED);
        $subject->resolve('fileadmin/news.csv');
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function notAllowedPathDataProvider(): iterable
    {
        yield 'empty' => ['', Typo3PathResolver::ERROR_NOT_ALLOWED];
        yield 'null byte' => ["fileadmin/news.csv\0.txt", Typo3PathResolver::ERROR_NOT_ALLOWED];
        yield 'parent directory' => ['fileadmin/../../news.csv', Typo3PathResolver::ERROR_NOT_ALLOWED];
        yield 'absolute path outside of the project' => ['/etc/passwd', Typo3PathResolver::ERROR_NOT_ALLOWED];
        yield 'stream wrapper' => ['php://filter/resource=/etc/passwd', Typo3PathResolver::ERROR_NOT_ALLOWED];
        yield 'url' => ['https://example.org/news.csv', Typo3PathResolver::ERROR_NOT_ALLOWED];
        yield 'public directory' => ['index.php', Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED];
        yield 'other directory of the public directory' => ['typo3temp/news.csv', Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED];
        yield 'directory itself' => ['fileadmin', Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED];
        yield 'similar directory name' => ['fileadmin_old/news.csv', Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED];
        yield 'php file' => ['fileadmin/news.php', Typo3PathResolver::ERROR_FILE_TYPE_NOT_ALLOWED];
        yield 'php with second extension' => ['fileadmin/news.php.csv', Typo3PathResolver::ERROR_FILE_TYPE_NOT_ALLOWED];
        yield 'htaccess' => ['fileadmin/.htaccess', Typo3PathResolver::ERROR_FILE_TYPE_NOT_ALLOWED];
    }

    #[Test]
    #[DataProvider('notAllowedPathDataProvider')]
    public function rejectsPathsThatAreNotAllowed(string $path, int $code): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileDirectories']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode($code);

        (new Typo3PathResolver())->resolve($path);
    }

    #[Test]
    public function rejectsAbsolutePathOutsideOfTheAllowedDirectories(): void
    {
        $this->expectExceptionCode(Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED);

        (new Typo3PathResolver(directories: ['fileadmin']))->resolve(Environment::getProjectPath() . '/config/system/settings.php.csv');
    }

    #[Test]
    public function rejectsExtensionPathsForTargets(): void
    {
        $this->expectExceptionCode(Typo3PathResolver::ERROR_NOT_ALLOWED);

        (new Typo3PathResolver(false))->resolve('EXT:t3import_export/Resources/Private/news.csv');
    }

    #[Test]
    public function appliesTheConfiguredFileDenyPattern(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] = '\\.csv$';

        $this->expectExceptionCode(Typo3PathResolver::ERROR_FILE_TYPE_NOT_ALLOWED);

        (new Typo3PathResolver(directories: ['fileadmin']))->resolve('fileadmin/news.csv');
    }

    #[Test]
    public function enforcesTheAllowedFileExtensionsOfTypo3IfEnabled(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'] = 'csv,txt';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] = 'jpg';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['miscfile_ext'] = 'zip';
        $subject = new Typo3PathResolver(directories: ['fileadmin']);

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.system.enforceAllowedFileExtensions'] = false;
        self::assertSame(Environment::getPublicPath() . '/fileadmin/news.xml', $subject->resolve('fileadmin/news.xml'));

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.system.enforceAllowedFileExtensions'] = true;
        self::assertSame(Environment::getPublicPath() . '/fileadmin/news.CSV', $subject->resolve('fileadmin/news.CSV'));
        $this->expectExceptionCode(Typo3PathResolver::ERROR_FILE_TYPE_NOT_ALLOWED);
        $subject->resolve('fileadmin/news.xml');
    }

    #[Test]
    public function checksOnlyTheFileTypeOfPathsOutsideOfTheAllowedDirectories(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] = '\\.php$';
        $subject = new Typo3PathResolver(directories: ['fileadmin']);

        $subject->assertFileTypeAllowed('/srv/storage/images/photo.jpg');

        $this->expectExceptionCode(Typo3PathResolver::ERROR_FILE_TYPE_NOT_ALLOWED);
        $subject->assertFileTypeAllowed('/srv/storage/images/photo.php');
    }

    #[Test]
    public function rejectsSymbolicLinkToAFileOutsideOfTheAllowedDirectories(): void
    {
        $directory = $this->createTestDirectory();
        file_put_contents($directory . '/outside/secret.csv', 'secret');
        symlink($directory . '/outside/secret.csv', $directory . '/allowed/link.csv');

        $this->expectExceptionCode(Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED);

        (new Typo3PathResolver(directories: [$directory . '/allowed']))->resolve($directory . '/allowed/link.csv');
    }

    #[Test]
    public function rejectsSymbolicLinkToAFileOutsideOfTheProject(): void
    {
        $directory = $this->createTestDirectory();
        $outside = tempnam(sys_get_temp_dir(), 't3import_export');
        self::assertIsString($outside);
        symlink($outside, $directory . '/allowed/link.csv');

        try {
            $this->expectExceptionCode(Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED);
            (new Typo3PathResolver(directories: [$directory . '/allowed']))->resolve($directory . '/allowed/link.csv');
        } finally {
            unlink($outside);
        }
    }

    #[Test]
    public function rejectsDanglingSymbolicLink(): void
    {
        $directory = $this->createTestDirectory();
        symlink($directory . '/outside/new.csv', $directory . '/allowed/link.csv');

        $this->expectExceptionCode(Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED);

        (new Typo3PathResolver(directories: [$directory . '/allowed']))->resolve($directory . '/allowed/link.csv');
    }

    #[Test]
    public function rejectsFilesInASymbolicLinkToADirectoryOutsideOfTheAllowedDirectories(): void
    {
        $directory = $this->createTestDirectory();
        file_put_contents($directory . '/outside/secret.csv', 'secret');
        symlink($directory . '/outside', $directory . '/allowed/linked');
        $subject = new Typo3PathResolver(directories: [$directory . '/allowed']);

        try {
            $subject->resolve($directory . '/allowed/linked/secret.csv');
            self::fail('An existing file in a linked directory must be rejected.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED, $exception->getCode());
        }

        $this->expectExceptionCode(Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED);
        $subject->resolve($directory . '/allowed/linked/new/news.csv');
    }

    #[Test]
    public function allowsFilesInAnAllowedDirectoryThatIsASymbolicLink(): void
    {
        $directory = $this->createTestDirectory();
        file_put_contents($directory . '/outside/news.csv', 'news');
        symlink($directory . '/outside', $directory . '/shared');
        $subject = new Typo3PathResolver(directories: [$directory . '/shared']);

        self::assertSame($directory . '/shared/news.csv', $subject->resolve($directory . '/shared/news.csv'));
        self::assertSame($directory . '/shared/import/new.csv', $subject->resolve($directory . '/shared/import/new.csv'));
    }

    #[Test]
    public function allowsFilesThatDoNotExistYetInAnAllowedDirectory(): void
    {
        $directory = $this->createTestDirectory();
        $subject = new Typo3PathResolver(directories: [$directory . '/allowed']);

        self::assertSame($directory . '/allowed/new.csv', $subject->resolve($directory . '/allowed/new.csv'));
        self::assertSame($directory . '/allowed/sub/new.csv', $subject->resolve($directory . '/allowed/sub/new.csv'));
    }

    #[Test]
    public function rejectsParentDirectorySegmentsInAnExistingAllowedDirectory(): void
    {
        $directory = $this->createTestDirectory();
        file_put_contents($directory . '/outside/secret.csv', 'secret');

        $this->expectExceptionCode(Typo3PathResolver::ERROR_NOT_ALLOWED);

        (new Typo3PathResolver(directories: [$directory . '/allowed']))->resolve($directory . '/allowed/../outside/secret.csv');
    }

    /**
     * Creates a directory in the var directory (inside of the project) with
     * the sub-directories `allowed` and `outside`.
     */
    private function createTestDirectory(): string
    {
        $this->testDirectory = Environment::getVarPath() . '/' . uniqid('Typo3PathResolverTest', true);
        mkdir($this->testDirectory . '/allowed', 0o777, true);
        mkdir($this->testDirectory . '/outside');

        return $this->testDirectory;
    }

    private static function removeDirectory(string $directory): void
    {
        if (!is_dir($directory) || is_link($directory)) {
            return;
        }
        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $directory . '/' . $entry;
            if (is_dir($path) && !is_link($path)) {
                self::removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($directory);
    }
}
