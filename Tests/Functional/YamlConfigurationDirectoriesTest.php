<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional;

use CPSIT\ImportExportCore\Configuration\ConfigurationHandler;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * YAML configuration directories set in the system configuration
 * (settings.php, additional.php) are not overwritten by ext_localconf.php
 */
class YamlConfigurationDirectoriesTest extends FunctionalTestCase
{
    protected const string DIRECTORY = 'EXT:t3import_export/Tests/Functional/Fixtures/YamlDirectory';

    protected array $testExtensionsToLoad = [
        't3import_export',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTCONF' => [
            't3import_export' => [
                'yamlConfigurationDirectories' => [self::DIRECTORY],
            ],
        ],
    ];

    #[Test]
    public function configuredDirectoryIsKept(): void
    {
        self::assertSame(
            [self::DIRECTORY],
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories']
        );
    }

    #[Test]
    public function tasksFromConfiguredDirectoryAreLoaded(): void
    {
        $configuration = $this->get(ConfigurationHandler::class)->getFullConfiguration();

        self::assertArrayHasKey('taskFromConfiguredDirectory', $configuration['import']['tasks'] ?? []);
    }
}
