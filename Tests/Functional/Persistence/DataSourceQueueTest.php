<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Persistence;

use CPSIT\T3importExport\Domain\Model\QueueItem;
use CPSIT\T3importExport\Persistence\DataSourceQueue;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Queue items are marked as processing when read and as finished
 * or failed afterwards (#93)
 */
class DataSourceQueueTest extends FunctionalTestCase
{
    protected const array CONFIGURATION = ['identifier' => 'import.foo', 'batchSize' => 2];

    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/queueItems.csv');
    }

    #[Test]
    public function getRecordsReadsOldestNewItemsAndMarksThemAsProcessing(): void
    {
        $subject = $this->get(DataSourceQueue::class);

        $records = $subject->getRecords(self::CONFIGURATION);

        self::assertSame([1, 2], array_column($records, 'uid'));
        self::assertSame(
            [1 => QueueItem::STATUS_PROCESSING, 2 => QueueItem::STATUS_PROCESSING, 3 => QueueItem::STATUS_NEW, 4 => QueueItem::STATUS_NEW, 5 => QueueItem::STATUS_FINISHED],
            $this->getStatusByUid()
        );
        self::assertGreaterThan(0, $this->getItem(1)['started_date']);
    }

    #[Test]
    public function completedItemsAreFinishedAndNotReadAgain(): void
    {
        $subject = $this->get(DataSourceQueue::class);
        $subject->getRecords(self::CONFIGURATION);

        $subject->complete(self::CONFIGURATION);

        self::assertSame(QueueItem::STATUS_FINISHED, $this->getStatusByUid()[1]);
        self::assertSame(QueueItem::STATUS_FINISHED, $this->getStatusByUid()[2]);
        self::assertGreaterThan(0, $this->getItem(2)['finished_date']);
        self::assertSame([3], array_column($subject->getRecords(self::CONFIGURATION), 'uid'));
    }

    #[Test]
    public function failedItemsAreMarkedAsFailed(): void
    {
        $subject = $this->get(DataSourceQueue::class);
        $subject->getRecords(self::CONFIGURATION);

        $subject->fail(self::CONFIGURATION);

        self::assertSame(QueueItem::STATUS_FAILED, $this->getStatusByUid()[1]);
        self::assertSame(QueueItem::STATUS_FAILED, $this->getStatusByUid()[2]);
        self::assertSame(QueueItem::STATUS_NEW, $this->getStatusByUid()[3]);
    }

    /**
     * @return array<int, int>
     */
    protected function getStatusByUid(): array
    {
        $rows = $this->getConnectionPool()->getConnectionForTable(QueueItem::TABLE)
            ->select(['uid', 'status'], QueueItem::TABLE, [], [], ['uid' => 'ASC'])
            ->fetchAllAssociative();

        return array_combine(
            array_map('intval', array_column($rows, 'uid')),
            array_map('intval', array_column($rows, 'status'))
        );
    }

    protected function getItem(int $uid): array
    {
        return $this->getConnectionPool()->getConnectionForTable(QueueItem::TABLE)
            ->select(['*'], QueueItem::TABLE, ['uid' => $uid])
            ->fetchAssociative();
    }
}
