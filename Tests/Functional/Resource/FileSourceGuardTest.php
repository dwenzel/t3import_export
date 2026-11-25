<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Resource;

use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Resource\FileSourceGuard;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
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
}
