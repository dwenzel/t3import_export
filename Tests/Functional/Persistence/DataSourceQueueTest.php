<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Persistence;

use CPSIT\ImportExportCore\Domain\Model\TaskResult;
use CPSIT\ImportExportCore\Factory\TransferTaskFactory;
use CPSIT\ImportExportCore\Persistence\NullDataTarget;
use CPSIT\ImportExportCore\Queue\QueueItemStatus;
use CPSIT\ImportExportCore\Queue\QueueRepositoryInterface;
use CPSIT\T3importExport\Dbal\QueueRepositoryFactory;
use CPSIT\T3importExport\Persistence\DataSourceQueue;
use CPSIT\T3importExport\Persistence\DataTargetDB;
use CPSIT\T3importExport\Service\TransferService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The queue source reads from the queue table of the core and marks the
 * items as processing, finished or failed.
 */
#[CoversClass(DataSourceQueue::class)]
class DataSourceQueueTest extends FunctionalTestCase
{
    protected const array CONFIGURATION = ['identifier' => 'import.foo', 'limit' => 2];

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
        $repository = $this->get(QueueRepositoryInterface::class);
        foreach (['a', 'b', 'c'] as $data) {
            $repository->enqueue('import.foo', ['data' => $data]);
        }
        $repository->enqueue('import.bar', ['data' => 'd']);
    }

    /**
     * @return list<mixed>
     */
    private static function read(iterable $records): array
    {
        return is_array($records) ? array_values($records) : iterator_to_array($records, false);
    }

    /**
     * @return array<string, int> status by data
     */
    private function getStatusByData(): array
    {
        $rows = $this->get(ConnectionPool::class)->getConnectionForTable(QueueRepositoryFactory::TABLE)
            ->select(['data', 'status'], QueueRepositoryFactory::TABLE, ['queue' => 'import.foo'], [], ['id' => 'ASC'])
            ->fetchAllAssociative();
        $status = [];
        foreach ($rows as $row) {
            $status[json_decode((string)$row['data'], true)['data']] = (int)$row['status'];
        }

        return $status;
    }

    #[Test]
    public function getRecordsReadsOldestNewItemsWithoutUidAndMarksThemAsProcessing(): void
    {
        $subject = $this->get(DataSourceQueue::class);

        $records = self::read($subject->getRecords(self::CONFIGURATION));

        self::assertSame([['data' => 'a'], ['data' => 'b']], $records);
        self::assertSame(
            ['a' => QueueItemStatus::Processing->value, 'b' => QueueItemStatus::Processing->value, 'c' => QueueItemStatus::New->value],
            $this->getStatusByData()
        );
    }

    #[Test]
    public function batchSizeOfEarlierVersionsIsUsedAsLimit(): void
    {
        $subject = $this->get(DataSourceQueue::class);

        $records = @self::read($subject->getRecords(['identifier' => 'import.foo', 'batchSize' => '1']));

        self::assertSame([['data' => 'a']], $records);
    }

    #[Test]
    public function completedItemsAreFinishedAndNotReadAgain(): void
    {
        $subject = $this->get(DataSourceQueue::class);
        self::read($subject->getRecords(self::CONFIGURATION));

        $subject->complete(new TaskResult());

        self::assertSame(
            ['a' => QueueItemStatus::Finished->value, 'b' => QueueItemStatus::Finished->value, 'c' => QueueItemStatus::New->value],
            $this->getStatusByData()
        );
        self::assertSame([['data' => 'c']], self::read($subject->getRecords(self::CONFIGURATION)));
    }

    #[Test]
    public function failedItemsAreMarkedAsFailed(): void
    {
        $subject = $this->get(DataSourceQueue::class);
        self::read($subject->getRecords(self::CONFIGURATION));

        $subject->fail();

        self::assertSame(
            ['a' => QueueItemStatus::Failed->value, 'b' => QueueItemStatus::Failed->value, 'c' => QueueItemStatus::New->value],
            $this->getStatusByData()
        );
    }

    /**
     * TransferService reads the records of all tasks (buildQueue) before it
     * processes them, so the items are claimed before the target writes.
     * If the task fails, the claimed items are marked as failed.
     */
    #[Test]
    public function itemsOfAFailingTaskRunByTheTransferServiceAreMarkedAsFailed(): void
    {
        $task = $this->get(TransferTaskFactory::class)->create('queueToMissingTable', [
            'source' => [
                'class' => DataSourceQueue::class,
                'config' => self::CONFIGURATION,
            ],
            'target' => [
                'class' => DataTargetDB::class,
                'config' => ['table' => 'tx_t3importexport_missing_table'],
            ],
        ]);

        try {
            $this->get(TransferService::class)->run([$task]);
            self::fail('The task should fail.');
        } catch (\Throwable $exception) {
            self::assertNotSame('The task should fail.', $exception->getMessage());
        }

        self::assertSame(
            ['a' => QueueItemStatus::Failed->value, 'b' => QueueItemStatus::Failed->value, 'c' => QueueItemStatus::New->value],
            $this->getStatusByData()
        );
    }

    #[Test]
    public function itemsOfATaskRunByTheTransferServiceAreFinished(): void
    {
        $task = $this->get(TransferTaskFactory::class)->create('queueToNull', [
            'source' => [
                'class' => DataSourceQueue::class,
                'config' => self::CONFIGURATION,
            ],
            'target' => [
                'class' => NullDataTarget::class,
            ],
        ]);

        $this->get(TransferService::class)->run([$task]);

        self::assertSame(
            ['a' => QueueItemStatus::Finished->value, 'b' => QueueItemStatus::Finished->value, 'c' => QueueItemStatus::New->value],
            $this->getStatusByData()
        );
    }
}
