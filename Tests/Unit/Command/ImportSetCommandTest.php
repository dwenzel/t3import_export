<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Command;

use CPSIT\ImportExportCore\Configuration\ConfigurationHandlerInterface;
use CPSIT\ImportExportCore\Configuration\YamlConfigurationLoader;
use CPSIT\ImportExportCore\Exception\ParseException;
use CPSIT\T3importExport\Command\ImportSetCommand;
use CPSIT\T3importExport\Command\Option\YamlConfigFileOption;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\ImportExportCore\Domain\Model\TransferSet;
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
 *  (c) 2022 Dirk Wenzel <wenzel@cps-it.de>
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
class ImportSetCommandTest extends TestCase
{
    protected const SET_IDENTIFIER = 'bar';
    protected const VALID_SET_CONFIGURATION = [
        'foo',
    ];
    protected const VALID_SETTINGS = [
        ImportSetCommand::SETTINGS_KEY => [
            'sets' => [
                self::SET_IDENTIFIER => self::VALID_SET_CONFIGURATION,
            ],
        ],
    ];

    protected ImportSetCommand $subject;
    protected ConfigurationHandlerInterface|MockObject $configurationHandler;
    protected TransferSetFactory|MockObject $transferSetFactory;
    protected TransferService|MockObject $transferService;
    protected array $settings = [];
    protected TransferSet $transferSet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transferSet = new TransferSet();
        $this->transferSetFactory = $this->createMock(TransferSetFactory::class);

        $this->transferSetFactory->method('get')
            ->willReturn($this->transferSet);

        /** @var ConfigurationHandlerInterface configurationHandler */
        $this->configurationHandler = $this->createMock(ConfigurationHandlerInterface::class);
        $this->configurationHandler->method('getFullConfiguration')
            ->willReturn(self::VALID_SETTINGS);

        $this->transferService = $this->createMock(TransferService::class);

        $this->subject = new ImportSetCommand(
            $this->transferSetFactory,
            $this->transferService,
            $this->configurationHandler
        );
    }

    #[Test]
    public function processPassesDryRunToTransferService(): void
    {
        $this->transferService->expects(self::once())
            ->method('run')
            ->with([], true);
        $this->subject->process(self::SET_IDENTIFIER, true);
    }

    #[Test]
    public function yamlConfigOptionIsRegistered(): void
    {
        $definition = $this->subject->getDefinition();

        self::assertTrue($definition->hasOption(YamlConfigFileOption::NAME));
        self::assertTrue($definition->hasShortcut(YamlConfigFileOption::SHORTCUT));
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
            'set' => self::SET_IDENTIFIER,
            '--' . YamlConfigFileOption::NAME => 'invalid.yaml',
        ]);

        self::assertSame(Command::FAILURE, $status);
        self::assertStringContainsString('Error loading YAML configuration', $commandTester->getDisplay());
        self::assertStringNotContainsString(ImportSetCommand::MESSAGE_SUCCESS, $commandTester->getDisplay());
    }

    #[Test]
    public function executeShowsWarningForAllowedHostsAsString(): void
    {
        $configuration = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts'] = 'data.example.org';

        try {
            $commandTester = new CommandTester($this->subject);
            $status = $commandTester->execute(['set' => self::SET_IDENTIFIER]);
        } finally {
            $GLOBALS['TYPO3_CONF_VARS'] = $configuration;
        }

        self::assertSame(Command::SUCCESS, $status);
        // the output is wrapped
        self::assertStringContainsString("['allowedHosts']", (string)preg_replace('/\\s+/', '', $commandTester->getDisplay()));
    }
}
