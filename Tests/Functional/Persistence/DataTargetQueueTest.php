<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Persistence;

use CPSIT\ImportExportCore\Queue\QueueItem;
use CPSIT\ImportExportCore\Queue\QueueItemStatus;
use CPSIT\T3importExport\Dbal\QueueRepositoryFactory;
use CPSIT\T3importExport\Persistence\DataSourceQueue;
use CPSIT\T3importExport\Persistence\DataTargetQueue;
use CPSIT\T3importExport\Upgrades\LegacyQueueItemConverter;
use CPSIT\T3importExport\Upgrades\QueueItemMigration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The queue target writes into the queue table of the core. Records that
 * were queued before version 4.0 and migrated by the upgrade wizard are
 * recognised as duplicates.
 */
#[CoversClass(DataTargetQueue::class)]
class DataTargetQueueTest extends FunctionalTestCase
{
    private const array CONFIGURATION = ['identifier' => 'import.foo'];

    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    /**
     * @return list<array{queue: string, checksum: string, data: string, status: int}>
     */
    private function fetchItems(): array
    {
        $rows = $this->get(ConnectionPool::class)->getConnectionForTable(QueueRepositoryFactory::TABLE)
            ->select(['queue', 'checksum', 'data', 'status'], QueueRepositoryFactory::TABLE, [], [], ['id' => 'ASC'])
            ->fetchAllAssociative();

        return array_map(
            static fn(array $row): array => [
                'queue' => (string)$row['queue'],
                'checksum' => (string)$row['checksum'],
                'data' => (string)$row['data'],
                'status' => (int)$row['status'],
            ],
            $rows
        );
    }

    private function migrateLegacyItem(string $data, int $status): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable(QueueItemMigration::LEGACY_TABLE)
            ->insert(QueueItemMigration::LEGACY_TABLE, [
                'identifier' => 'import.foo',
                'checksum' => sha1($data . 'import.foo'),
                'data' => $data,
                'status' => $status,
                'created_date' => 1700000000,
            ]);
        $wizard = new QueueItemMigration($this->get(ConnectionPool::class), new LegacyQueueItemConverter());
        $wizard->setOutput(new BufferedOutput());
        self::assertTrue($wizard->executeUpdate());
    }

    #[Test]
    public function persistWritesTheDataFieldIntoTheQueueTable(): void
    {
        $subject = $this->get(DataTargetQueue::class);

        self::assertTrue($subject->persist(['data' => '{"title":"ä"}', 'uid' => 3], self::CONFIGURATION));

        self::assertSame(
            [[
                'queue' => 'import.foo',
                'checksum' => QueueItem::checksum('import.foo', ['data' => '{"title":"ä"}']),
                'data' => '{"data":"{\"title\":\"ä\"}"}',
                'status' => QueueItemStatus::New->value,
            ]],
            $this->fetchItems()
        );
    }

    #[Test]
    public function recordOfAMigratedItemIsNotQueuedAgain(): void
    {
        $this->migrateLegacyItem('{"id":1}', 2);
        $migrated = $this->fetchItems();
        self::assertCount(1, $migrated);

        // a record as the queue source of earlier versions returned it
        $result = $this->get(DataTargetQueue::class)->persist(['uid' => 9, 'data' => '{"id":1}'], self::CONFIGURATION);

        self::assertFalse($result);
        self::assertSame($migrated, $this->fetchItems());
    }

    #[Test]
    public function recordOfAMigratedItemIsSetBackToNewWithAllowUpdate(): void
    {
        $this->migrateLegacyItem('{"id":1}', 3);

        $result = $this->get(DataTargetQueue::class)
            ->persist(['data' => '{"id":1}'], self::CONFIGURATION + ['allowUpdate' => true]);

        self::assertTrue($result);
        $items = $this->fetchItems();
        self::assertCount(1, $items);
        self::assertSame(QueueItemStatus::New->value, $items[0]['status']);
    }

    #[Test]
    public function sourceReadsTheRecordsOfMigratedAndNewItemsAlike(): void
    {
        $this->migrateLegacyItem('a', 0);
        $this->get(DataTargetQueue::class)->persist(['data' => 'b'], self::CONFIGURATION);

        $records = $this->get(DataSourceQueue::class)->getRecords(self::CONFIGURATION);

        self::assertSame([['data' => 'a'], ['data' => 'b']], iterator_to_array($records, false));
    }
}
