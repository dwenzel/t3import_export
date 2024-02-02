<?php
namespace CPSIT\T3importExport\Tests\Functional\Service;

/***************************************************************
 *  Copyright notice
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use CPSIT\T3importExport\Domain\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DataTransferProcessorTest
 * Functional tests for CPSIT\T3importExport\Service\DataTransferProcessor
 *
 * @package CPSIT\T3importExport\Tests\Functional\Service
 */
class ImportProcessorTest extends FunctionalTestCase
{

    /**
     * @var DataTransferProcessor
     */
    protected $importProcessor;

    /**
     * @var TransferTaskFactory
     */
    protected $transferTaskFactory;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/t3import_export'];

    protected function setUp(): void
    {
        $this->importProcessor = $this->objectManager->get(DataTransferProcessor::class);
        $this->transferTaskFactory = GeneralUtility::makeInstance(TransferTaskFactory::class);
        $this->importDataSet(__DIR__ . '/../Fixtures/importProcessorBuildQueue.xml');
    }

    /**
     * @test
     */
    public function buildQueueFindsRecords()
    {
        $taskIdentifier = 'findFeUser';

        $settings = [
            'source' => [
                'config' => [
                    'table' => 'fe_users',
                    'where' => 'name="findFeUser"'
                ]
            ],
            'target' => [

            ]
        ];
        $importTask = $this->transferTaskFactory->get($settings, $taskIdentifier);
        $importDemand = new TaskDemand();
        $importDemand->setTasks([$importTask]);

        $this->importProcessor->buildQueue($importDemand);

        $queue = $this->importProcessor->getQueue();
        $this->assertArrayHasKey(
            $taskIdentifier,
            $queue
        );
        $this->assertEquals(
            1,
            is_countable($queue[$taskIdentifier]) ? count($queue[$taskIdentifier]) : 0
        );
        $this->assertEquals(
            $queue[$taskIdentifier][0]['name'],
            'findFeUser'
        );
    }
}
