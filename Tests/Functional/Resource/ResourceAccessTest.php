<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Resource;

use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Resource\ResourceAccess;
use CPSIT\T3importExport\Resource\Typo3PathResolver;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Source files of components that build the path from a value of a record
 */
class ResourceAccessTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    #[Test]
    public function resolveSourceFileRejectsExtensionPathWithoutExtensionSourcePath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(Typo3PathResolver::ERROR_NOT_ALLOWED);

        (new ResourceAccess())->resolveSourceFile('EXT:t3import_export/ext_emconf.php', null);
    }

    #[Test]
    public function resolveSourceFileAllowsFileBelowTheExtensionSourcePath(): void
    {
        $sourcePath = 'EXT:t3import_export/Tests/Functional/Fixtures/Files/';

        self::assertSame(
            ExtensionManagementUtility::extPath('t3import_export') . 'Tests/Functional/Fixtures/Files/courses.csv',
            (new ResourceAccess())->resolveSourceFile($sourcePath . 'courses.csv', $sourcePath)
        );
    }
}
