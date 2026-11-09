<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Domain\Model\QueueItem;
use CPSIT\T3importExport\Domain\Repository\QueueItemRepository;
use CPSIT\T3importExport\Persistence\DataSourceQueue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataSourceQueueTest extends TestCase
{
    protected const array CONFIGURATION = ['identifier' => 'import.foo'];
    protected const array RECORDS = [
        ['uid' => 3, 'data' => 'a'],
        ['uid' => 5, 'data' => 'b'],
    ];

    protected DataSourceQueue $subject;

    protected QueueItemRepository|MockObject $repository;

    /**
     * @var array<int, array{0: int[], 1: int}>
     */
    protected array $statusUpdates = [];

    protected function setUp(): void
    {
        $this->repository = $this->getMockBuilder(QueueItemRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->repository->method('updateStatus')
            ->willReturnCallback(function (array $uids, int $status): void {
                $this->statusUpdates[] = [$uids, $status];
            });

        $this->subject = new DataSourceQueue($this->repository);
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
            'identifier must begin with import. or export.' => [
                [DataSourceQueue::KEY_IDENTIFIER => 'foo.bar'],
            ],
            'batch size must be an integer' => [
                [DataSourceQueue::KEY_IDENTIFIER => 'import.foo', DataSourceQueue::KEY_BATCH_SIZE => 'many'],
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
            'export identifier with batch size' => [
                [DataSourceQueue::KEY_IDENTIFIER => 'export.foo', DataSourceQueue::KEY_BATCH_SIZE => '10'],
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
    public function getRecordsMarksItemsAsProcessing(): void
    {
        $this->repository->method('findNewByIdentifier')->willReturn(self::RECORDS);

        self::assertSame(self::RECORDS, $this->subject->getRecords(self::CONFIGURATION));
        self::assertSame([[[3, 5], QueueItem::STATUS_PROCESSING]], $this->statusUpdates);
    }

    #[Test]
    public function completeMarksClaimedItemsAsFinishedOnce(): void
    {
        $this->repository->method('findNewByIdentifier')->willReturn(self::RECORDS);
        $this->subject->getRecords(self::CONFIGURATION);
        $this->statusUpdates = [];

        $this->subject->complete(self::CONFIGURATION);
        $this->subject->complete(self::CONFIGURATION);

        self::assertSame(
            [[[3, 5], QueueItem::STATUS_FINISHED], [[], QueueItem::STATUS_FINISHED]],
            $this->statusUpdates
        );
    }

    #[Test]
    public function failMarksClaimedItemsAsFailed(): void
    {
        $this->repository->method('findNewByIdentifier')->willReturn(self::RECORDS);
        $this->subject->getRecords(self::CONFIGURATION);
        $this->statusUpdates = [];

        $this->subject->fail(self::CONFIGURATION);

        self::assertSame([[[3, 5], QueueItem::STATUS_FAILED]], $this->statusUpdates);
    }
}
