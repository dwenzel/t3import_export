<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Command;

use CPSIT\T3importExport\Command\ExportSetCommand;
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
    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    #[Test]
    public function convertTypoScriptCommandIsRegisteredButNotSchedulable(): void
    {
        $commandRegistry = $this->get(CommandRegistry::class);

        self::assertTrue($commandRegistry->has('t3import-export:convert-typoscript'));
        self::assertSame('t3import-export:convert-typoscript', $commandRegistry->get('t3import-export:convert-typoscript')->getName());
        self::assertNotContains('t3import-export:convert-typoscript', $this->getSchedulableCommandNames());
    }

    #[Test]
    public function importAndExportCommandsAreSchedulable(): void
    {
        $schedulableCommandNames = $this->getSchedulableCommandNames();

        foreach ([ImportTaskCommand::DEFAULT_NAME, ImportSetCommand::DEFAULT_NAME, ExportSetCommand::DEFAULT_NAME] as $name) {
            self::assertContains($name, $schedulableCommandNames);
        }
    }

    /**
     * @return list<string>
     */
    protected function getSchedulableCommandNames(): array
    {
        return array_keys(iterator_to_array($this->get(CommandRegistry::class)->getSchedulableCommands()));
    }
}
