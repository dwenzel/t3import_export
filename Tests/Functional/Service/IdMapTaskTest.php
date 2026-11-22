<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Service;

use CPSIT\ImportExportCore\Dbal\DataTargetDBAL;
use CPSIT\ImportExportCore\Domain\Model\TransferTaskInterface;
use CPSIT\ImportExportCore\Factory\TransferTaskFactory;
use CPSIT\ImportExportCore\Processor\ValueRulesPreProcessor;
use CPSIT\ImportExportCore\State\Component\ResolveReferencePreProcessor;
use CPSIT\ImportExportCore\State\Component\TargetIdPreProcessor;
use CPSIT\ImportExportCore\State\Component\UnchangedRecordFilter;
use CPSIT\T3importExport\Dbal\IdMapFactory;
use CPSIT\T3importExport\Persistence\DataSourceDB;
use CPSIT\T3importExport\Persistence\DataTargetDB;
use CPSIT\T3importExport\Service\TransferService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tasks with an ID map (`idMap`) and the state components of the core on the
 * ID map table of the extension: pages are imported as categories, and
 * content elements as categories below the category of their page.
 */
#[CoversClass(IdMapFactory::class)]
#[CoversClass(TransferService::class)]
class IdMapTaskTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    private const string TARGET_TABLE = 'sys_category';

    /**
     * Options of the ID map shared by the task and its components: the
     * target ID is set in `__identity`, from which DataTargetDB takes the
     * `uid` of the row to update
     */
    private const array PAGES_ID_MAP = [
        'namespace' => 'pages',
        'sourceIdField' => 'uid',
        'targetIdField' => '__identity',
    ];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/dataSourceDBPages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/dataTargetDBCategory.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/dataTargetDBContent.csv');
    }

    /**
     * Imports the pages as categories.
     *
     * @param bool $filterUnchanged with UnchangedRecordFilter
     */
    private function createPagesTask(bool $filterUnchanged = true): TransferTaskInterface
    {
        $settings = [
            'source' => [
                'class' => DataSourceDB::class,
                'config' => ['table' => 'pages', 'fields' => 'uid,title', 'orderBy' => 'uid'],
            ],
            'idMap' => self::PAGES_ID_MAP,
            'preProcessors' => [
                10 => ['class' => TargetIdPreProcessor::class, 'config' => self::PAGES_ID_MAP],
            ],
            'target' => [
                'class' => DataTargetDB::class,
                'config' => ['table' => self::TARGET_TABLE, 'unsetKeys' => 'uid'],
            ],
        ];
        if ($filterUnchanged) {
            $settings['filters'] = [
                10 => ['class' => UnchangedRecordFilter::class, 'config' => self::PAGES_ID_MAP],
            ];
        }

        return $this->get(TransferTaskFactory::class)->create('pagesToCategories', $settings);
    }

    /**
     * Imports the content elements as categories; `parent` is the category of
     * the page of the content element.
     *
     * @param array<string, mixed> $resolveReference further options of ResolveReferencePreProcessor
     */
    private function createContentTask(array $resolveReference = []): TransferTaskInterface
    {
        return $this->get(TransferTaskFactory::class)->create('contentToCategories', [
            'source' => [
                'class' => DataSourceDB::class,
                'config' => ['table' => 'tt_content', 'fields' => 'uid,pid,header', 'orderBy' => 'uid'],
            ],
            'idMap' => ['namespace' => 'tt_content', 'sourceIdField' => 'uid'],
            'preProcessors' => [
                10 => [
                    'class' => ValueRulesPreProcessor::class,
                    'config' => ['rules' => [
                        ['rule' => 'rename', 'field' => 'title', 'from' => 'header'],
                        ['rule' => 'rename', 'field' => 'parent', 'from' => 'pid'],
                    ]],
                ],
                20 => [
                    'class' => ResolveReferencePreProcessor::class,
                    'config' => ['field' => 'parent', 'namespace' => 'pages'] + $resolveReference,
                ],
            ],
            'target' => [
                'class' => DataTargetDB::class,
                'config' => ['table' => self::TARGET_TABLE, 'unsetKeys' => 'uid'],
            ],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function categories(): array
    {
        return $this->get(ConnectionPool::class)
            ->getConnectionForTable(self::TARGET_TABLE)
            ->select(['uid', 'title', 'parent'], self::TARGET_TABLE, [], [], ['uid' => 'ASC'])
            ->fetchAllAssociative();
    }

    /**
     * Target IDs by source ID
     *
     * @return array<string, string>
     */
    private function idMap(string $namespace): array
    {
        $rows = $this->get(ConnectionPool::class)
            ->getConnectionForTable(IdMapFactory::TABLE)
            ->select(['source_id', 'target_id'], IdMapFactory::TABLE, ['namespace' => $namespace])
            ->fetchAllAssociative();
        $map = array_column($rows, 'target_id', 'source_id');
        ksort($map);

        return array_combine(array_map('strval', array_keys($map)), array_map('strval', $map));
    }

    /**
     * @return array<string, string>
     */
    private function checksums(string $namespace): array
    {
        $rows = $this->get(ConnectionPool::class)
            ->getConnectionForTable(IdMapFactory::TABLE)
            ->select(['source_id', 'checksum'], IdMapFactory::TABLE, ['namespace' => $namespace])
            ->fetchAllAssociative();

        return array_column($rows, 'checksum', 'source_id');
    }

    /**
     * @param array<string, mixed> $values
     */
    private function update(string $table, array $values, int $uid): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable($table)->update($table, $values, ['uid' => $uid]);
    }

    /**
     * uid of the category of each page, as the first run writes them: after
     * the category with uid 3 of the fixture
     *
     * @return array<string, string>
     */
    private static function expectedPageMap(): array
    {
        return ['1' => '4', '2' => '5', '3' => '6', '4' => '7', '5' => '8', '6' => '9', '7' => '10', '8' => '11'];
    }

    #[Test]
    public function firstRunInsertsTheRecordsAndStoresTheirUids(): void
    {
        $this->get(TransferService::class)->run([$this->createPagesTask()]);

        self::assertSame(self::expectedPageMap(), $this->idMap('pages'));
        $categories = $this->categories();
        self::assertCount(9, $categories);
        self::assertSame(['uid' => 4, 'title' => 'Root', 'parent' => 0], $categories[1]);
        self::assertSame(['uid' => 11, 'title' => 'Subpage 2.1', 'parent' => 0], $categories[8]);
        self::assertNotContains('', $this->checksums('pages'));
    }

    /**
     * TargetIdPreProcessor sets the uid of the earlier run in `__identity`,
     * so DataTargetDB updates the rows.
     */
    #[Test]
    public function secondRunUpdatesTheRecordsOfTheFirstRun(): void
    {
        $this->get(TransferService::class)->run([$this->createPagesTask(false)]);
        $this->update('pages', ['title' => 'Root renamed'], 1);
        $this->update(self::TARGET_TABLE, ['title' => 'Changed in TYPO3'], 5);

        $this->get(TransferService::class)->run([$this->createPagesTask(false)]);

        self::assertSame(self::expectedPageMap(), $this->idMap('pages'));
        $categories = $this->categories();
        self::assertCount(9, $categories);
        self::assertSame(['uid' => 4, 'title' => 'Root renamed', 'parent' => 0], $categories[1]);
        // without UnchangedRecordFilter, unchanged records are written again
        self::assertSame(['uid' => 5, 'title' => 'Subpage 1', 'parent' => 0], $categories[2]);
    }

    #[Test]
    public function unchangedRecordFilterSkipsUnchangedRecords(): void
    {
        $this->get(TransferService::class)->run([$this->createPagesTask()]);
        $checksums = $this->checksums('pages');
        $this->update('pages', ['title' => 'Root renamed'], 1);
        $this->update(self::TARGET_TABLE, ['title' => 'Changed in TYPO3'], 5);

        $this->get(TransferService::class)->run([$this->createPagesTask()]);

        $categories = $this->categories();
        self::assertCount(9, $categories);
        self::assertSame(['uid' => 4, 'title' => 'Root renamed', 'parent' => 0], $categories[1]);
        // page 2 did not change, so its category was not written
        self::assertSame(['uid' => 5, 'title' => 'Changed in TYPO3', 'parent' => 0], $categories[2]);
        $newChecksums = $this->checksums('pages');
        self::assertNotSame($checksums['1'], $newChecksums['1']);
        self::assertSame(array_diff_key($checksums, ['1' => '']), array_diff_key($newChecksums, ['1' => '']));
    }

    /**
     * With DataTargetDBAL of the core, the target ID is set in `uid` itself:
     * TargetIdPreProcessor removes the source `uid` of new records and sets
     * the mapped `uid` of records written before.
     */
    #[Test]
    public function dataTargetDbalUpdatesByTheUidOfTheIdMap(): void
    {
        $idMap = ['namespace' => 'pages', 'sourceIdField' => 'uid'];
        $settings = [
            'source' => [
                'class' => DataSourceDB::class,
                'config' => ['table' => 'pages', 'fields' => 'uid,title', 'orderBy' => 'uid'],
            ],
            'idMap' => $idMap,
            'preProcessors' => [
                10 => ['class' => TargetIdPreProcessor::class, 'config' => $idMap],
            ],
            'filters' => [
                10 => ['class' => UnchangedRecordFilter::class, 'config' => $idMap],
            ],
            'target' => [
                'class' => DataTargetDBAL::class,
                'config' => ['table' => self::TARGET_TABLE, 'key' => ['uid'], 'idField' => 'uid'],
            ],
        ];
        $this->get(TransferService::class)->run([$this->get(TransferTaskFactory::class)->create('pagesToCategories', $settings)]);
        $this->update('pages', ['title' => 'Root renamed'], 1);
        $this->update(self::TARGET_TABLE, ['title' => 'Changed in TYPO3'], 5);

        $this->get(TransferService::class)->run([$this->get(TransferTaskFactory::class)->create('pagesToCategories', $settings)]);

        self::assertSame(self::expectedPageMap(), $this->idMap('pages'));
        $categories = $this->categories();
        self::assertCount(9, $categories);
        self::assertSame(['uid' => 4, 'title' => 'Root renamed', 'parent' => 0], $categories[1]);
        self::assertSame(['uid' => 5, 'title' => 'Changed in TYPO3', 'parent' => 0], $categories[2]);
    }

    /**
     * The mapping of a row deleted in TYPO3 stays; DataTargetDB writes
     * nothing for an `__identity` without row.
     */
    #[Test]
    public function recordOfADeletedRowIsNotWrittenAgain(): void
    {
        $this->get(TransferService::class)->run([$this->createPagesTask(false)]);
        $this->get(ConnectionPool::class)->getConnectionForTable(self::TARGET_TABLE)->delete(self::TARGET_TABLE, ['uid' => 5]);

        $this->get(TransferService::class)->run([$this->createPagesTask(false)]);

        self::assertSame(self::expectedPageMap(), $this->idMap('pages'));
        self::assertSame([3, 4, 6, 7, 8, 9, 10, 11], array_column($this->categories(), 'uid'));
    }

    #[Test]
    public function resolveReferencePreProcessorSetsTheUidsOfImportedRecords(): void
    {
        $this->get(TransferService::class)->run([$this->createPagesTask(), $this->createContentTask()]);

        // content elements 1 and 2 are on page 1, 3 on page 2
        self::assertSame(['1' => '12', '2' => '13', '3' => '14'], $this->idMap('tt_content'));
        self::assertSame(
            [
                ['uid' => 12, 'title' => 'First', 'parent' => 4],
                ['uid' => 13, 'title' => 'Second', 'parent' => 4],
                ['uid' => 14, 'title' => 'Third', 'parent' => 5],
            ],
            \array_slice($this->categories(), 9)
        );
    }

    #[Test]
    public function unresolvedReferenceFailsTheTaskByDefault(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1794600012);

        $this->get(TransferService::class)->run([$this->createContentTask()]);
    }

    #[Test]
    public function unresolvedReferenceCanBeEmptied(): void
    {
        $this->get(TransferService::class)->run([$this->createContentTask(['onMissing' => 'empty'])]);

        self::assertSame([0, 0, 0], array_column(\array_slice($this->categories(), 1), 'parent'));
    }
}
