<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\ImportExportCore\Domain\Model\TaskResult;
use CPSIT\ImportExportCore\Persistence\DataSourceQueue as CoreDataSourceQueue;
use CPSIT\ImportExportCore\Persistence\FailureAwareInterface;
use CPSIT\ImportExportCore\Queue\InMemoryQueueRepository;
use CPSIT\ImportExportCore\Queue\QueueItemStatus;
use CPSIT\T3importExport\Persistence\CompletableSourceInterface;
use CPSIT\T3importExport\Persistence\DataSourceQueue;
use CPSIT\T3importExport\Utility\DeprecationUtility;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2026 Dirk Wenzel <wenzel@cps-it.de>
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
#[CoversClass(DataSourceQueue::class)]
class DataSourceQueueTest extends TestCase
{
    private const string QUEUE = 'import.foo';

    protected DataSourceQueue $subject;
    protected InMemoryQueueRepository $repository;

    /**
     * @var list<string>
     */
    private array $deprecations = [];

    protected function setUp(): void
    {
        DeprecationUtility::reset();
        $this->repository = new InMemoryQueueRepository();
        $this->subject = new DataSourceQueue($this->repository);
        set_error_handler(function (int $errno, string $message): bool {
            $this->deprecations[] = $message;
            return true;
        }, E_USER_DEPRECATED);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        DeprecationUtility::reset();
    }

    private function enqueue(string ...$values): void
    {
        foreach ($values as $value) {
            $this->repository->enqueue(self::QUEUE, ['data' => $value]);
        }
    }

    /**
     * @param array<string, mixed> $configuration
     * @return list<mixed>
     */
    private function read(array $configuration): array
    {
        $records = $this->subject->getRecords($configuration);

        return is_array($records) ? array_values($records) : iterator_to_array($records, false);
    }

    #[Test]
    public function isAdapterOnTheQueueSourceOfTheCore(): void
    {
        self::assertInstanceOf(CoreDataSourceQueue::class, $this->subject);
        self::assertInstanceOf(FailureAwareInterface::class, $this->subject);
        self::assertInstanceOf(CompletableSourceInterface::class, $this->subject);
    }

    public static function invalidConfigurationDataProvider(): array
    {
        return [
            'empty configuration' => [
                [],
            ],
            'identifier not a string' => [
                [DataSourceQueue::KEY_IDENTIFIER => 3],
            ],
            'empty identifier' => [
                [DataSourceQueue::KEY_IDENTIFIER => ''],
            ],
            'batch size not an integer' => [
                [DataSourceQueue::KEY_IDENTIFIER => self::QUEUE, DataSourceQueue::KEY_BATCH_SIZE => 'many'],
            ],
            'limit not an integer' => [
                [DataSourceQueue::KEY_IDENTIFIER => self::QUEUE, DataSourceQueue::KEY_LIMIT => 'many'],
            ],
            'limit zero' => [
                [DataSourceQueue::KEY_IDENTIFIER => self::QUEUE, DataSourceQueue::KEY_LIMIT => 0],
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidConfigurationDataProvider')]
    public function isConfigurationValidReturnsFalseForInvalidConfiguration(array $configuration): void
    {
        self::assertFalse($this->subject->isConfigurationValid($configuration));
    }

    public static function validConfigurationDataProvider(): array
    {
        return [
            'import identifier' => [
                [DataSourceQueue::KEY_IDENTIFIER => 'import.foo'],
            ],
            'export identifier' => [
                [DataSourceQueue::KEY_IDENTIFIER => 'export.foo'],
            ],
            'identifier without prefix' => [
                [DataSourceQueue::KEY_IDENTIFIER => 'events'],
            ],
            'limit' => [
                [DataSourceQueue::KEY_IDENTIFIER => 'import.foo', DataSourceQueue::KEY_LIMIT => 5],
            ],
            'batch size as string' => [
                [DataSourceQueue::KEY_IDENTIFIER => 'export.foo', DataSourceQueue::KEY_BATCH_SIZE => '10'],
            ],
            'batch size as integer' => [
                [DataSourceQueue::KEY_IDENTIFIER => 'export.foo', DataSourceQueue::KEY_BATCH_SIZE => 10],
            ],
            'empty batch size' => [
                [DataSourceQueue::KEY_IDENTIFIER => 'export.foo', DataSourceQueue::KEY_BATCH_SIZE => ''],
            ],
        ];
    }

    #[Test]
    #[DataProvider('validConfigurationDataProvider')]
    public function isConfigurationValidReturnsTrueForValidConfiguration(array $configuration): void
    {
        self::assertTrue($this->subject->isConfigurationValid($configuration));
    }

    #[Test]
    public function getRecordsReturnsTheRecordsWithoutUidAndClaimsThem(): void
    {
        $this->enqueue('a', 'b', 'c');

        $records = $this->read([DataSourceQueue::KEY_IDENTIFIER => self::QUEUE, DataSourceQueue::KEY_LIMIT => 2]);

        self::assertSame([['data' => 'a'], ['data' => 'b']], $records);
        self::assertSame(2, $this->repository->count(self::QUEUE, QueueItemStatus::Processing));
        self::assertSame(1, $this->repository->count(self::QUEUE, QueueItemStatus::New));
        self::assertSame([], $this->deprecations);
    }

    #[Test]
    public function getRecordsReadsTenItemsByDefault(): void
    {
        $this->enqueue(...array_map('strval', range(1, 12)));

        self::assertCount(10, $this->read([DataSourceQueue::KEY_IDENTIFIER => self::QUEUE]));
    }

    #[Test]
    public function batchSizeIsUsedAsLimitAndDeprecated(): void
    {
        $this->enqueue('a', 'b', 'c');

        $records = $this->read([DataSourceQueue::KEY_IDENTIFIER => self::QUEUE, DataSourceQueue::KEY_BATCH_SIZE => '2']);

        self::assertSame([['data' => 'a'], ['data' => 'b']], $records);
        self::assertCount(1, $this->deprecations);
        self::assertStringContainsString('batchSize', $this->deprecations[0]);
    }

    #[Test]
    public function limitTakesPrecedenceOverBatchSize(): void
    {
        $this->enqueue('a', 'b', 'c');

        $records = $this->read([
            DataSourceQueue::KEY_IDENTIFIER => self::QUEUE,
            DataSourceQueue::KEY_LIMIT => 1,
            DataSourceQueue::KEY_BATCH_SIZE => 2,
        ]);

        self::assertSame([['data' => 'a']], $records);
    }

    #[Test]
    public function emptyBatchSizeUsesTheDefault(): void
    {
        $this->enqueue('a', 'b');

        self::assertCount(2, $this->read([DataSourceQueue::KEY_IDENTIFIER => self::QUEUE, DataSourceQueue::KEY_BATCH_SIZE => '']));
    }

    #[Test]
    public function completeMarksClaimedItemsAsFinishedOnce(): void
    {
        $this->enqueue('a', 'b');
        $this->read([DataSourceQueue::KEY_IDENTIFIER => self::QUEUE]);

        $this->subject->complete(new TaskResult());
        $this->subject->fail();

        self::assertSame(2, $this->repository->count(self::QUEUE, QueueItemStatus::Finished));
        self::assertSame(0, $this->repository->count(self::QUEUE, QueueItemStatus::Failed));
    }

    #[Test]
    public function failWithoutErrorMarksClaimedItemsAsFailed(): void
    {
        $this->enqueue('a', 'b', 'c');
        $this->read([DataSourceQueue::KEY_IDENTIFIER => self::QUEUE, DataSourceQueue::KEY_LIMIT => 2]);

        $this->subject->fail();

        self::assertSame(2, $this->repository->count(self::QUEUE, QueueItemStatus::Failed));
        self::assertSame(1, $this->repository->count(self::QUEUE, QueueItemStatus::New));
    }

    #[Test]
    public function failWithErrorMarksClaimedItemsAsFailed(): void
    {
        $this->enqueue('a');
        $this->read([DataSourceQueue::KEY_IDENTIFIER => self::QUEUE]);

        $this->subject->fail(new \RuntimeException('failed'));

        self::assertSame(1, $this->repository->count(self::QUEUE, QueueItemStatus::Failed));
    }
}
