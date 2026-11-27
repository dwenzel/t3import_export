<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Resource;

use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Resource\FileSourceGuard;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * EXT: paths of source files (only as configured source path)
 */
class FileSourceGuardTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    protected const string SOURCE_PATH = 'EXT:t3import_export/Resources/Public/Icons/';

    #[Test]
    public function resolveFileAcceptsFileBelowExtensionSourcePath(): void
    {
        $guard = new FileSourceGuard();

        self::assertSame(
            realpath(ExtensionManagementUtility::extPath('t3import_export') . 'Resources/Public/Icons/ext_icon.svg'),
            $guard->resolveFile($guard->buildLocation(self::SOURCE_PATH, 'ext_icon.svg'), self::SOURCE_PATH)
        );
    }

    #[Test]
    public function resolveFileRejectsExtensionPathOfRecord(): void
    {
        $guard = new FileSourceGuard();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_PATH_NOT_ALLOWED);

        $guard->resolveFile($guard->buildLocation(null, 'EXT:core/composer.json'), null);
    }

    #[Test]
    public function resolveFileRejectsDotDotSegmentsBelowExtensionSourcePath(): void
    {
        $guard = new FileSourceGuard();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_PATH_NOT_ALLOWED);

        $guard->resolveFile($guard->buildLocation(self::SOURCE_PATH, '../../../composer.json'), self::SOURCE_PATH);
    }

    #[Test]
    public function resolveFileRejectsSourcePathOfUnknownExtension(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED);

        (new FileSourceGuard())->resolveFile('EXT:unknown_extension/file.txt', 'EXT:unknown_extension/');
    }

    #[Test]
    public function resolveFileUsesExistingExtensionDirectoryWithoutTrailingSlash(): void
    {
        $guard = new FileSourceGuard();
        $sourcePath = rtrim(self::SOURCE_PATH, '/');

        self::assertSame(
            realpath(ExtensionManagementUtility::extPath('t3import_export') . 'Resources/Public/Icons/ext_icon.svg'),
            $guard->resolveFile($guard->buildLocation($sourcePath, '/ext_icon.svg'), $sourcePath)
        );
    }

    #[Test]
    public function resolveFileRejectsSiblingOfLocalSourceDirectoryWithoutTrailingSlash(): void
    {
        $directory = Environment::getPublicPath() . '/fileadmin/import';
        GeneralUtility::mkdir_deep($directory . '/a');
        GeneralUtility::mkdir_deep($directory . '/ab');
        file_put_contents($directory . '/a/image.jpg', 'image');
        file_put_contents($directory . '/ab/image.jpg', 'image');
        $guard = new FileSourceGuard();

        self::assertSame(
            realpath($directory . '/a/image.jpg'),
            $guard->resolveFile($guard->buildLocation('fileadmin/import/a', '/image.jpg'), 'fileadmin/import/a')
        );
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED);

        $guard->resolveFile($guard->buildLocation('fileadmin/import/a', 'b/image.jpg'), 'fileadmin/import/a');
    }

    #[Test]
    public function resolveFileRejectsFileInAllowedDirectoryWithoutSourcePath(): void
    {
        $directory = Environment::getVarPath() . '/import';
        GeneralUtility::mkdir_deep($directory);
        file_put_contents($directory . '/secret.csv', 'secret');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_PATH_NOT_ALLOWED);

        (new FileSourceGuard())->resolveFile($directory . '/secret.csv', null);
    }
}
