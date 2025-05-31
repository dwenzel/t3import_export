<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Command;

use CPSIT\T3importExport\Command\ImportSetCommand;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;
use CPSIT\T3importExport\Domain\Model\TransferSet;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use CPSIT\ImportExportCore\Configuration\ConfigurationHandlerInterface;

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
        'settings' => [
            ImportSetCommand::SETTINGS_KEY => [
                'sets' => [
                    self::SET_IDENTIFIER => self::VALID_SET_CONFIGURATION,
                ],
            ],
        ],
    ];

    protected ImportSetCommand $subject;
    protected ConfigurationHandlerInterface|MockObject $configurationHandler;
    protected TaskDemand|MockObject $taskDemand;
    protected TransferSetFactory|MockObject $transferSetFactory;
    protected DataTransferProcessor|MockObject $dataTransferProcessor;
    protected array $settings = [];
    protected TransferSet|MockObject $transferSet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transferSet = $this->createMock(TransferSet::class);
        $this->transferSetFactory = $this->createMock(TransferSetFactory::class);

        $this->transferSetFactory->method('get')
            ->willReturn($this->transferSet);

        /** @var ConfigurationHandlerInterface configurationHandler */
        $this->configurationHandler = $this->createMock(ConfigurationHandlerInterface::class);
        $this->configurationHandler->method('getFullConfiguration')
            ->willReturn(self::VALID_SETTINGS);

        $this->taskDemand = $this->createMock(TaskDemand::class);
        GeneralUtility::addInstance(TaskDemand::class, $this->taskDemand);
        $this->dataTransferProcessor = $this->createMock(DataTransferProcessor::class);

        $this->subject = new ImportSetCommand(
            $this->transferSetFactory,
            $this->dataTransferProcessor,
            $this->configurationHandler
        );
    }

    #[Test]
    public function testMethodProcessOfDataTransferProcessorIsNotCallWithDryRun(): void
    {
        $this->dataTransferProcessor->expects($this->never())
            ->method('process');
        $this->subject->process(self::SET_IDENTIFIER, true);
    }
}
