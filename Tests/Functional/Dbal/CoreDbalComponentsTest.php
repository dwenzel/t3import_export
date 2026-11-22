<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Dbal;

use CPSIT\ImportExportCore\Dbal\ConnectionProviderInterface;
use CPSIT\ImportExportCore\Dbal\DataSourceDBAL;
use CPSIT\ImportExportCore\Dbal\DataTargetDBAL;
use CPSIT\ImportExportCore\Domain\Model\TaskResult;
use CPSIT\ImportExportCore\Exception\MissingResourceException;
use CPSIT\ImportExportCore\Factory\TransferTaskFactory;
use CPSIT\ImportExportCore\Processor\SetValuesPreProcessor;
use CPSIT\T3importExport\Dbal\Typo3ConnectionProvider;
use CPSIT\T3importExport\Service\TransferService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The DBAL source and target of the core on TYPO3 tables, with the
 * connections of TYPO3.
 */
#[CoversClass(Typo3ConnectionProvider::class)]
class CoreDbalComponentsTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    private const string SECOND_CONNECTION = 'Second';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION])) {
            $this->get(ConnectionPool::class)->getConnectionByName(self::SECOND_CONNECTION)->close();
            unset(
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION],
                $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']['pages']
            );
        }
        parent::tearDown();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchPages(): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder->select('uid', 'pid', 'title')
            ->from('pages')
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    #[Test]
    public function connectionProviderIsTheProviderOfTheExtension(): void
    {
        self::assertInstanceOf(Typo3ConnectionProvider::class, $this->get(ConnectionProviderInterface::class));
    }

    #[Test]
    public function defaultConnectionOfTheCoreIsTheDefaultConnectionOfTypo3(): void
    {
        $provider = $this->get(ConnectionProviderInterface::class);
        $connectionPool = $this->get(ConnectionPool::class);

        self::assertTrue($provider->hasConnection(ConnectionProviderInterface::DEFAULT_CONNECTION));
        self::assertSame(
            $connectionPool->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME),
            $provider->getConnection(ConnectionProviderInterface::DEFAULT_CONNECTION)
        );
        self::assertSame(
            $provider->getConnection(ConnectionPool::DEFAULT_CONNECTION_NAME),
            $provider->getConnection(ConnectionProviderInterface::DEFAULT_CONNECTION)
        );
    }

    #[Test]
    public function connectionThatIsNotConfiguredThrows(): void
    {
        $this->expectException(MissingResourceException::class);

        $this->get(ConnectionProviderInterface::class)->getConnection('Missing');
    }

    #[Test]
    public function sourceReadsTypo3Table(): void
    {
        $source = GeneralUtility::makeInstance(DataSourceDBAL::class);
        $configuration = [
            'table' => 'pages',
            'fields' => ['uid', 'title'],
            'where' => ['pid' => 1],
            'orderBy' => ['uid' => 'DESC'],
        ];

        self::assertTrue($source->isConfigurationValid($configuration));
        self::assertSame(
            [
                ['uid' => 3, 'title' => 'Subpage 2'],
                ['uid' => 2, 'title' => 'Subpage 1'],
            ],
            iterator_to_array($source->getRecords($configuration), false)
        );
    }

    #[Test]
    public function sourceReadsTypo3TableInPagesOfTheCursorField(): void
    {
        $source = GeneralUtility::makeInstance(DataSourceDBAL::class);
        $configuration = [
            'table' => 'pages',
            'connection' => ConnectionPool::DEFAULT_CONNECTION_NAME,
            'fields' => ['uid', 'title'],
            'filter' => ['field' => 'uid', 'op' => '>', 'value' => 1],
            'cursorField' => 'uid',
            'pageSize' => 1,
        ];

        self::assertSame(
            [
                ['uid' => 2, 'title' => 'Subpage 1'],
                ['uid' => 3, 'title' => 'Subpage 2'],
            ],
            iterator_to_array($source->getRecords($configuration), false)
        );
        self::assertSame('3', $source->getCursor());
    }

    #[Test]
    public function targetInsertsAndUpdatesRowsOfTypo3Table(): void
    {
        $target = GeneralUtility::makeInstance(DataTargetDBAL::class);
        $configuration = ['table' => 'pages', 'key' => ['uid'], 'idField' => 'uid'];
        $updated = ['uid' => 2, 'title' => 'Updated'];
        $inserted = ['pid' => 1, 'title' => 'Inserted'];

        self::assertTrue($target->isConfigurationValid($configuration));
        self::assertFalse($target->persist($updated, $configuration));
        self::assertTrue($target->persist($inserted, $configuration));
        self::assertSame('2', $target->getTargetId($updated));
        $insertedId = $target->getTargetId($inserted);
        $target->persistAll(null, $configuration);

        self::assertSame(
            [
                ['uid' => 1, 'pid' => 0, 'title' => 'Root'],
                ['uid' => 2, 'pid' => 1, 'title' => 'Updated'],
                ['uid' => 3, 'pid' => 1, 'title' => 'Subpage 2'],
                ['uid' => (int)$insertedId, 'pid' => 1, 'title' => 'Inserted'],
            ],
            $this->fetchPages()
        );
        self::assertGreaterThan(3, (int)$insertedId);
    }

    #[Test]
    public function targetWritesBatchesInTransactionsOfTypo3Connection(): void
    {
        $target = GeneralUtility::makeInstance(DataTargetDBAL::class);
        $configuration = ['table' => 'pages', 'key' => ['uid'], 'idField' => 'uid'];

        $target->persist(['uid' => 2, 'title' => 'Committed'], $configuration);
        $target->flushBatch(new TaskResult());
        $target->persist(['uid' => 3, 'title' => 'Rolled back'], $configuration);
        $target->fail(new \RuntimeException('Task failed'));

        self::assertFalse($this->get(ConnectionPool::class)->getConnectionForTable('pages')->isTransactionActive());
        self::assertSame(
            ['Root', 'Committed', 'Subpage 2'],
            array_column($this->fetchPages(), 'title')
        );
    }

    #[Test]
    public function taskCopiesRowsWithSourceAndTargetOfTheCore(): void
    {
        $task = $this->get(TransferTaskFactory::class)->create('copyPages', [
            'source' => [
                'class' => DataSourceDBAL::class,
                'config' => [
                    'table' => 'pages',
                    'fields' => ['title'],
                    'where' => ['pid' => 1],
                    'orderBy' => ['uid' => 'ASC'],
                ],
            ],
            'preProcessors' => [
                10 => [
                    'class' => SetValuesPreProcessor::class,
                    'config' => ['values' => ['pid' => 3]],
                ],
            ],
            'target' => [
                'class' => DataTargetDBAL::class,
                'config' => ['table' => 'pages', 'idField' => 'uid'],
            ],
        ]);

        $this->get(TransferService::class)->run([$task]);

        $copies = array_values(array_filter($this->fetchPages(), static fn (array $page): bool => $page['pid'] === 3));
        self::assertSame(['Subpage 1', 'Subpage 2'], array_column($copies, 'title'));
    }

    #[Test]
    public function targetWritesWithTheConnectionOfTheTableMapping(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION]
            = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME];
        $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']['pages'] = self::SECOND_CONNECTION;
        $provider = $this->get(Typo3ConnectionProvider::class);
        $connectionName = $provider->getConnectionNameForTable('pages');
        $target = GeneralUtility::makeInstance(DataTargetDBAL::class);
        $configuration = ['table' => 'pages', 'connection' => $connectionName, 'key' => ['uid'], 'idField' => 'uid'];

        self::assertSame(self::SECOND_CONNECTION, $connectionName);
        self::assertSame($provider->getConnectionForTable('pages'), $provider->getConnection($connectionName));
        self::assertNotSame(
            $provider->getConnection(ConnectionProviderInterface::DEFAULT_CONNECTION),
            $provider->getConnection($connectionName)
        );

        $target->persist(['uid' => 2, 'title' => 'Second connection'], $configuration);
        $target->persistAll(null, $configuration);

        self::assertSame('Second connection', $this->fetchPages()[1]['title']);
    }
}
