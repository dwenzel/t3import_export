<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Resource;

use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Resource\FileSourceGuard;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * EXT: paths of source files
 */
class FileSourceGuardTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    #[Test]
    public function resolveFileAcceptsFileInExtension(): void
    {
        self::assertSame(
            ExtensionManagementUtility::extPath('t3import_export') . 'Resources/Public/Icons/ext_icon.svg',
            (new FileSourceGuard())->resolveFile('EXT:t3import_export/Resources/Public/Icons/ext_icon.svg')
        );
    }

    #[Test]
    public function resolveFileRejectsDotDotSegmentsInExtensionPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_PATH_NOT_ALLOWED);

        (new FileSourceGuard())->resolveFile('EXT:t3import_export/../../../config/system/settings.php');
    }

    #[Test]
    public function resolveFileRejectsPathOfUnknownExtension(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileSourceGuard::ERROR_DIRECTORY_NOT_ALLOWED);

        (new FileSourceGuard())->resolveFile('EXT:unknown_extension/file.txt');
    }
}
