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
use CPSIT\T3importExport\Service\DataTransferProcessor;
use CPSIT\T3importExport\Domain\Factory\ImportTaskFactory;
use CPSIT\T3importExport\Domain\Model\Dto\ImportDemand;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ImportProcessorTest
 * Functional tests for CPSIT\T3importExport\Service\DataTransferProcessor
 *
 * @package CPSIT\T3importExport\Tests\Functional\Service
 */
class ImportProcessorTest extends FunctionalTestCase {

	/**
	 * @var DataTransferProcessor
	 */
	protected $importProcessor;

    /**
     * @var ImportTaskFactory
     */
    protected $importTaskFactory;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

	/**
	 * @var array
	 */
	protected $testExtensionsToLoad = ['typo3conf/ext/t3import_export'];

	public function setUp() {
		parent::setUp();
        $this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->importProcessor = $this->objectManager->get(DataTransferProcessor::class);
        $this->importTaskFactory = $this->objectManager->get(ImportTaskFactory::class);
        $this->importDataSet(__DIR__ . '/../Fixtures/importProcessorBuildQueue.xml');
    }

	/**
	 * @test
	 */
	public function buildQueueFindsRecords() {
        $taskIdentifier = 'findFeUser';
        $localDatabaseIdentifier = 'typo3local';
        $this->registerTypo3Database($localDatabaseIdentifier);

		$settings = [
            'source' => [
                'identifier' => $localDatabaseIdentifier,
                'config' => [
                    'table' => 'fe_users',
                    'where' => 'name="findFeUser"'
                ]
            ],
            'target' => [

            ]
        ];
        $importTask = $this->importTaskFactory->get($settings, $taskIdentifier);
        $importDemand = new ImportDemand();
        $importDemand->setTasks([$importTask]);

		$this->importProcessor->buildQueue($importDemand);

        $queue = $this->importProcessor->getQueue();
		$this->assertArrayHasKey(
			$taskIdentifier,
            $queue
		);
        $this->assertEquals(
            1,
            count($queue[$taskIdentifier])
        );
        $this->assertEquals(
            $queue[$taskIdentifier][0]['name'],
            'findFeUser'
        );
	}

    /**
     * @param $localDatabaseIdentifier
     */
    protected function registerTypo3Database($localDatabaseIdentifier)
    {
        DatabaseConnectionService::register(
            $localDatabaseIdentifier,
            $GLOBALS['TYPO3_CONF_VARS']['DB']['host'],
            $GLOBALS['TYPO3_CONF_VARS']['DB']['database'],
            $GLOBALS['TYPO3_CONF_VARS']['DB']['username'],
            $GLOBALS['TYPO3_CONF_VARS']['DB']['password'],
            $GLOBALS['TYPO3_CONF_VARS']['DB']['port']
        );
    }
}
