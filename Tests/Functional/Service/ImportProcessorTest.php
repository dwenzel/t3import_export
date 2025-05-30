<?php

declare(strict_types=1);
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
use CPSIT\T3importExport\Domain\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use Doctrine\DBAL\ParameterType;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class DataTransferProcessorTest
 * Functional tests for CPSIT\T3importExport\Service\DataTransferProcessor
 */
class ImportProcessorTest extends FunctionalTestCase
{
    /**
     * @var DataTransferProcessor
     */
    protected DataTransferProcessor $importProcessor;

    /**
     * @var TransferTaskFactory
     */
    protected TransferTaskFactory $transferTaskFactory;

    /**
     * @var array
     */
    protected array $testExtensionsToLoad = [
        't3import_export'
    ];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->importProcessor = new DataTransferProcessor();
        $this->transferTaskFactory = GeneralUtility::makeInstance(TransferTaskFactory::class);

        // Import CSV fixture for TYPO3 13 compatibility
        //@todo use other table to avoid dependency on fe_users table
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
    }

    #[Test]
    public function buildQueueFindsRecords(): void
    {
        // Check if the fixture was loaded properly
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('fe_users');
        $count = $queryBuilder
            ->count('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter('Subpage 1', ParameterType::STRING))
            )
            ->executeQuery()
            ->fetchOne();

        $this->assertEquals(1, $count, 'Fixture data should be loaded');

        $taskIdentifier = 'findSubpage-1';
        $title = "Subpage 1";
        $settings = [
            'source' => [
                'config' => [
                    'table' => 'pages',
                    'where' => 'title="Subpage 1"',
                ],
            ],
            'target' => [
                'config' => [
                    'targetClass' => PageRepository::class
                ]
            ],
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
            $title,
            $queue[$taskIdentifier][0]['title'],

        );
    }

    #[Test]
    public function dataCanBeImportedWithSourceCSVandTargetDB(): void
    {
        $taskIdentifier = 'importCSV';
        $settings = [
            'source' => [
                'class' => 'CPSIT\T3importExport\Persistence\DataSourceCSV',
                'config' => [
                    'file' => 'EXT:t3import_export/Tests/Functional/Fixtures/importCSVPages.csv',
                ],
            ],
            'target' => [
                'class' => 'CPSIT\T3importExport\Persistence\DataTargetDB',
                'config' => [
                    'table' => 'pages',
                ]
            ],
        ];
        $pageTitle = 'Imported Subpage 3';
        $importTask = $this->transferTaskFactory->get($settings, $taskIdentifier);
        $importDemand = new TaskDemand();
        $importDemand->setTasks([$importTask]);

        // assert that page does not exist
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('pages');
        $count = $queryBuilder
            ->count('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()
                    ->eq('title',
                        $queryBuilder->createNamedParameter($pageTitle, ParameterType::STRING))
            )
            ->executeQuery()
            ->fetchOne();

        $this->assertEquals(0, $count, 'Page should not exist yet');

        // build queue and process
        $this->importProcessor->buildQueue($importDemand);
        $this->importProcessor->process($importDemand);

        // assert that page exists after import
        $count = $queryBuilder
            ->count('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()
                    ->eq('title',
                        $queryBuilder->createNamedParameter($pageTitle, ParameterType::STRING))
            )
            ->executeQuery()
            ->fetchOne();

        $this->assertEquals(1, $count, 'Page should exist now');


    }
}
