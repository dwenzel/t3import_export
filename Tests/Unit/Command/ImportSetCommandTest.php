<?php

namespace CPSIT\T3importExport\Tests\Unit\Command;

use CPSIT\T3importExport\Command\ImportSetCommand;
use CPSIT\T3importExport\Configuration\Extension;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;
use CPSIT\T3importExport\Domain\Model\TransferSet;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

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
        'foo'
    ];
    protected const VALID_SETTINGS = [
        'settings' => [
            ImportSetCommand::SETTINGS_KEY => [
                'sets' => [
                    self::SET_IDENTIFIER => self::VALID_SET_CONFIGURATION
                ]
            ]
        ]
    ];

    protected ImportSetCommand $subject;
    /**
     * @var ObjectProphecy<ConfigurationManagerInterface>
     */
    protected $configurationManager;

    /**
     * @var ObjectProphecy<TaskDemand>&TaskDemand
     */
    protected $taskDemand;

    /** @var TransferSetFactory&ObjectProphecy<TransferSetFactory>  */
    protected $transferSetFactory;

    /**
     * @var DataTransferProcessor&ObjectProphecy<DataTransferProcessor>
     */
    protected $dataTransferProcessor;

    protected array $settings = [];
    /**
     * @var ObjectProphecy<TransferSet>
     */
    protected $transferSet;

    public function setUp(): void
    {
        $this->markTestIncomplete();
        parent::setUp();
        $this->transferSet = $this->prophesize(TransferSet::class);
        $this->transferSetFactory = $this->prophesize(TransferSetFactory::class);

        $this->transferSetFactory->get($this->settings)
            ->willReturn($this->transferSet->reveal());

        /** @var ConfigurationManagerInterface configurationManager */
        $this->configurationManager = $this->prophesize(ConfigurationManagerInterface::class);
        $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            't3importexport'
        )
            ->willReturn(self::VALID_SETTINGS);

        $this->taskDemand = $this->prophesize(TaskDemand::class);
        GeneralUtility::addInstance(TaskDemand::class, $this->taskDemand->reveal());
        $this->dataTransferProcessor = $this->prophesize(DataTransferProcessor::class);

        /** @var DataTransferProcessor $processor */
        $processor = $this->dataTransferProcessor->reveal();
        $this->subject = new ImportSetCommand(
            'foo',
            $this->transferSetFactory->reveal(),
            $processor,
            $this->configurationManager->reveal()
        );
    }

    /** @noinspection PhpUndefinedMethodInspection */
    public function testMethodProcessOfDataTransferProcessorIsNotCallWithDryRun(): void
    {
        $this->dataTransferProcessor->process($this->taskDemand)
            ->shouldNotBeCalled();
        $this->subject->process(self::SET_IDENTIFIER, true);
    }


}
