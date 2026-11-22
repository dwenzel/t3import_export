<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Command;

use CPSIT\ImportExportCore\Configuration\ConfigurationHandlerInterface;
use CPSIT\ImportExportCore\Configuration\YamlConfigurationLoader;
use CPSIT\ImportExportCore\Domain\Model\TransferTask;
use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\ImportExportCore\Exception\ParseException;
use CPSIT\ImportExportCore\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Command\Argument\TaskArgument;
use CPSIT\T3importExport\Command\ExportTaskCommand;
use CPSIT\T3importExport\Command\Option\YamlConfigFileOption;
use CPSIT\T3importExport\Service\TransferService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2026 Dirk Wenzel <wenzel@cps-it.de>
 *  All rights reserved
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class ExportTaskCommandTest extends TestCase
{
    protected const TASK_IDENTIFIER = 'bar';
    protected const VALID_TASK_CONFIGURATION = [
        'source' => ['class' => 'foo'],
        'target' => ['class' => 'baz'],
    ];
    protected const VALID_SETTINGS = [
        ExportTaskCommand::SETTINGS_KEY => [
            'tasks' => [
                self::TASK_IDENTIFIER => self::VALID_TASK_CONFIGURATION,
            ],
        ],
    ];

    protected ExportTaskCommand $subject;
    protected ConfigurationHandlerInterface&MockObject $configurationHandler;
    protected TransferTaskFactory&MockObject $transferTaskFactory;
    protected TransferService&MockObject $transferService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transferTaskFactory = $this->createMock(TransferTaskFactory::class);
        $this->configurationHandler = $this->createMock(ConfigurationHandlerInterface::class);
        $this->configurationHandler->method('getFullConfiguration')
            ->willReturn(self::VALID_SETTINGS);
        $this->transferService = $this->createMock(TransferService::class);

        $this->subject = new ExportTaskCommand(
            $this->transferTaskFactory,
            $this->transferService,
            $this->configurationHandler
        );
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    #[Test]
    public function commandHasExpectedName(): void
    {
        self::assertSame('t3import-export:export-task', $this->subject->getName());
        self::assertSame(['export:task'], ExportTaskCommand::COMMAND_ALIASES);
    }

    #[Test]
    public function taskArgumentAndYamlConfigOptionAreRegistered(): void
    {
        $definition = $this->subject->getDefinition();

        self::assertTrue($definition->hasArgument(TaskArgument::NAME));
        self::assertTrue($definition->hasOption(YamlConfigFileOption::NAME));
        self::assertTrue($definition->hasShortcut(YamlConfigFileOption::SHORTCUT));
    }

    #[Test]
    public function processCreatesTaskFromExportSettingsAndRunsIt(): void
    {
        $task = new TransferTask();
        $this->transferTaskFactory->expects(self::once())
            ->method('create')
            ->with(self::TASK_IDENTIFIER, self::VALID_TASK_CONFIGURATION)
            ->willReturn($task);
        $this->transferService->expects(self::once())
            ->method('run')
            ->with([$task], true);

        self::assertSame(
            Command::SUCCESS,
            $this->subject->process(self::TASK_IDENTIFIER, true)
        );
    }

    #[Test]
    public function processReturnsInvalidForUnknownTask(): void
    {
        $this->transferService->expects(self::never())->method('run');

        self::assertSame(Command::INVALID, $this->subject->process('unknown'));
    }

    #[Test]
    public function executeRunsTaskAndReportsSuccess(): void
    {
        $this->transferTaskFactory->method('create')->willReturn(new TransferTask());
        $this->transferService->expects(self::once())->method('run');

        $commandTester = new CommandTester($this->subject);
        $status = $commandTester->execute([TaskArgument::NAME => self::TASK_IDENTIFIER]);

        self::assertSame(Command::SUCCESS, $status);
        self::assertStringContainsString(ExportTaskCommand::MESSAGE_SUCCESS, $commandTester->getDisplay());
    }

    #[Test]
    public function executeReturnsInvalidForMissingTaskConfiguration(): void
    {
        $this->transferService->expects(self::never())->method('run');

        $commandTester = new CommandTester($this->subject);
        $status = $commandTester->execute([TaskArgument::NAME => 'unknown']);

        self::assertSame(Command::INVALID, $status);
        self::assertStringContainsString('No configuration found', $commandTester->getDisplay());
    }

    #[Test]
    public function executeFailsForInvalidTaskConfiguration(): void
    {
        $this->transferTaskFactory->method('create')
            ->willThrowException(new InvalidConfigurationException('source is missing', 1451206701));
        $this->transferService->expects(self::never())->method('run');

        $commandTester = new CommandTester($this->subject);
        $status = $commandTester->execute([TaskArgument::NAME => self::TASK_IDENTIFIER]);

        self::assertSame(Command::FAILURE, $status);
        self::assertStringContainsString('source is missing', $commandTester->getDisplay());
        self::assertStringNotContainsString(ExportTaskCommand::MESSAGE_SUCCESS, $commandTester->getDisplay());
    }

    #[Test]
    public function executeFailsIfYamlConfigurationCannotBeLoaded(): void
    {
        GeneralUtility::addInstance(
            YamlConfigurationLoader::class,
            $this->createMock(YamlConfigurationLoader::class)
        );
        $this->configurationHandler->method('addConfiguration')
            ->willThrowException(new ParseException('Error parsing YAML file'));
        $this->transferService->expects(self::never())->method('run');

        $commandTester = new CommandTester($this->subject);
        $status = $commandTester->execute([
            TaskArgument::NAME => self::TASK_IDENTIFIER,
            '--' . YamlConfigFileOption::NAME => 'invalid.yaml',
        ]);

        self::assertSame(Command::FAILURE, $status);
        self::assertStringContainsString('Error loading YAML configuration', $commandTester->getDisplay());
    }

    #[Test]
    public function executeReturnsInvalidForEmptyTaskIdentifier(): void
    {
        $this->transferTaskFactory->expects(self::never())->method('create');
        $this->transferService->expects(self::never())->method('run');

        $commandTester = new CommandTester($this->subject);
        $status = $commandTester->execute([TaskArgument::NAME => '']);

        self::assertSame(Command::INVALID, $status);
        self::assertStringContainsString('must not be omitted', $commandTester->getDisplay());
        self::assertStringNotContainsString(ExportTaskCommand::MESSAGE_STARTING, $commandTester->getDisplay());
    }
}
