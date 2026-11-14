<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional;

use CPSIT\T3importExport\Configuration\Extension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Icons are registered through Configuration/Icons.php
 */
class IconRegistrationTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    /**
     * @return array<string, array{string, string}>
     */
    public static function iconDataProvider(): array
    {
        return [
            'jobs' => [
                Extension::SVG_ICON_IDENTIFIER_JOBS,
                'EXT:t3import_export/Resources/Public/Icons/tx_t3importexport_domain_model_job.svg',
            ],
            'import module' => [
                Extension::SVG_ICON_IDENTIFIER_MODULE_IMPORT,
                'EXT:t3import_export/Resources/Public/Icons/module_import.svg',
            ],
            'export module' => [
                Extension::SVG_ICON_IDENTIFIER_MODULE_EXPORT,
                'EXT:t3import_export/Resources/Public/Icons/module_export.svg',
            ],
        ];
    }

    #[Test]
    #[DataProvider('iconDataProvider')]
    public function iconIsRegistered(string $identifier, string $source): void
    {
        $registry = $this->get(IconRegistry::class);

        self::assertTrue($registry->isRegistered($identifier));
        $configuration = $registry->getIconConfigurationByIdentifier($identifier);
        self::assertSame(SvgIconProvider::class, $configuration['provider']);
        self::assertSame($source, $configuration['options']['source']);
    }
}
