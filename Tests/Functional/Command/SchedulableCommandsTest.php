<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Command;

use CPSIT\T3importExport\Command\ExportSetCommand;
use CPSIT\T3importExport\Command\ExportTaskCommand;
use CPSIT\T3importExport\Command\ImportSetCommand;
use CPSIT\T3importExport\Command\ImportTaskCommand;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Commands offered by the scheduler ("Execute console commands")
 */
class SchedulableCommandsTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    #[Test]
    public function convertTypoScriptCommandIsRegisteredButNotSchedulable(): void
    {
        $commandRegistry = $this->get(CommandRegistry::class);

        self::assertTrue($commandRegistry->has('t3import-export:convert-typoscript'));
        self::assertArrayNotHasKey(
            't3import-export:convert-typoscript',
            $commandRegistry->getSchedulableCommandsConfiguration()
        );
    }

    #[Test]
    public function importAndExportCommandsAreSchedulable(): void
    {
        $schedulableCommands = $this->get(CommandRegistry::class)->getSchedulableCommandsConfiguration();

        foreach ([ImportTaskCommand::DEFAULT_NAME, ImportSetCommand::DEFAULT_NAME, ExportTaskCommand::DEFAULT_NAME, ExportSetCommand::DEFAULT_NAME] as $name) {
            self::assertArrayHasKey($name, $schedulableCommands);
        }
    }
}
