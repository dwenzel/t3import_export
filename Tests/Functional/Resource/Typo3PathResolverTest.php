<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Resource;

use CPSIT\T3importExport\Resource\Typo3PathResolver;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Symbolic links in `EXT:` paths
 */
class Typo3PathResolverTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    private const string FIXTURES = 'Tests/Functional/Fixtures/Files/';

    /**
     * @var list<string>
     */
    private array $files = [];

    private string $outsideDirectory = '';

    protected function tearDown(): void
    {
        foreach (array_reverse($this->files) as $file) {
            if (is_link($file) || is_file($file)) {
                unlink($file);
            }
        }
        if ($this->outsideDirectory !== '' && is_dir($this->outsideDirectory)) {
            rmdir($this->outsideDirectory);
        }
        parent::tearDown();
    }

    #[Test]
    public function allowsFileInTheExtension(): void
    {
        $path = 'EXT:t3import_export/' . self::FIXTURES . 'courses.csv';

        self::assertSame(
            ExtensionManagementUtility::extPath('t3import_export') . self::FIXTURES . 'courses.csv',
            (new Typo3PathResolver())->resolve($path)
        );
    }

    #[Test]
    public function allowsFileInTheExtensionWithComposerName(): void
    {
        $path = 'EXT:cpsit/t3import_export/' . self::FIXTURES . 'courses.csv';

        self::assertFileExists((new Typo3PathResolver())->resolve($path));
    }

    #[Test]
    public function rejectsSymbolicLinkToAFileOutsideOfTheExtensionWithComposerName(): void
    {
        $target = $this->createFile($this->outsideDirectory());
        $name = $this->createLink($target);

        $this->expectExceptionCode(Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED);

        (new Typo3PathResolver(directories: [Environment::getPublicPath() . '/fileadmin']))
            ->resolve('EXT:cpsit/t3import_export/' . self::FIXTURES . $name);
    }

    #[Test]
    public function allowsSymbolicLinkToAFileInTheExtension(): void
    {
        $name = $this->createLink(ExtensionManagementUtility::extPath('t3import_export') . self::FIXTURES . 'courses.csv');

        self::assertFileExists((new Typo3PathResolver())->resolve('EXT:t3import_export/' . self::FIXTURES . $name));
    }

    #[Test]
    public function allowsSymbolicLinkToAFileInAnAllowedDirectory(): void
    {
        $directory = $this->outsideDirectory();
        $target = $this->createFile($directory);
        $name = $this->createLink($target);

        self::assertFileExists(
            (new Typo3PathResolver(directories: [$directory]))
                ->resolve('EXT:t3import_export/' . self::FIXTURES . $name)
        );
    }

    #[Test]
    public function rejectsSymbolicLinkToAFileOutsideOfTheExtension(): void
    {
        // the test instance is inside the directory of the extension
        $target = $this->createFile($this->outsideDirectory());
        $name = $this->createLink($target);

        $this->expectExceptionCode(Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED);

        (new Typo3PathResolver(directories: [Environment::getPublicPath() . '/fileadmin']))
            ->resolve('EXT:t3import_export/' . self::FIXTURES . $name);
    }

    #[Test]
    public function rejectsFileInASymbolicLinkToADirectoryOutsideOfTheExtension(): void
    {
        $target = $this->createFile($this->outsideDirectory());
        $name = $this->createLink(\dirname($target));

        $this->expectExceptionCode(Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED);

        (new Typo3PathResolver(directories: [Environment::getPublicPath() . '/fileadmin']))
            ->resolve('EXT:t3import_export/' . self::FIXTURES . $name . '/' . basename($target));
    }

    /**
     * A directory outside of the extension and of the test instance
     */
    private function outsideDirectory(): string
    {
        $this->outsideDirectory = sys_get_temp_dir() . '/' . uniqid('Typo3PathResolverTest', true);
        mkdir($this->outsideDirectory);
        self::assertStringStartsNotWith(
            (string)realpath(ExtensionManagementUtility::extPath('t3import_export')),
            (string)realpath($this->outsideDirectory)
        );

        return $this->outsideDirectory;
    }

    private function createFile(string $directory): string
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }
        $file = $directory . '/' . uniqid('Typo3PathResolverTest', true) . '.csv';
        file_put_contents($file, 'id' . "\n");
        $this->files[] = $file;

        return $file;
    }

    /**
     * Creates a symbolic link in the fixtures of the extension
     *
     * @return string name of the link
     */
    private function createLink(string $target): string
    {
        $name = uniqid('Typo3PathResolverTest', true) . (is_dir($target) ? '' : '.csv');
        $link = ExtensionManagementUtility::extPath('t3import_export') . self::FIXTURES . $name;
        symlink($target, $link);
        $this->files[] = $link;

        return $name;
    }
}
