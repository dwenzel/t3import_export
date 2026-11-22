<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Upgrades;

use CPSIT\ImportExportCore\Queue\QueueItem;
use CPSIT\ImportExportCore\Queue\QueueItemStatus;
use CPSIT\T3importExport\Upgrades\LegacyQueueItemConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(LegacyQueueItemConverter::class)]
class LegacyQueueItemConverterTest extends TestCase
{
    private LegacyQueueItemConverter $subject;

    #[\Override]
    protected function setUp(): void
    {
        $this->subject = new LegacyQueueItemConverter();
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function legacyRow(array $row = []): array
    {
        return $row + [
            'uid' => 1,
            'identifier' => 'import.events',
            'checksum' => 'legacy',
            'data' => '{"id":7}',
            'status' => 0,
            'created_date' => 0,
            'started_date' => 0,
            'finished_date' => 0,
        ];
    }

    #[Test]
    public function identifierBecomesQueue(): void
    {
        $item = $this->subject->convert(self::legacyRow());

        self::assertNotNull($item);
        self::assertSame('import.events', $item['queue']);
    }

    #[Test]
    public function dataIsStoredAsRecordWithFieldData(): void
    {
        $item = $this->subject->convert(self::legacyRow(['data' => 'a|b']));

        self::assertNotNull($item);
        self::assertSame(QueueItem::encode(['data' => 'a|b']), $item['data']);
    }

    #[Test]
    public function checksumIsTheChecksumOfTheCoreForTheRecord(): void
    {
        $item = $this->subject->convert(self::legacyRow(['data' => 'a|b']));

        self::assertNotNull($item);
        self::assertSame(QueueItem::checksum('import.events', ['data' => 'a|b']), $item['checksum']);
    }

    #[Test]
    public function missingDataBecomesEmptyString(): void
    {
        $item = $this->subject->convert(self::legacyRow(['data' => null]));

        self::assertNotNull($item);
        self::assertSame(QueueItem::encode(['data' => '']), $item['data']);
    }

    #[Test]
    public function dataFromStreamIsRead(): void
    {
        $stream = fopen('php://memory', 'r+b');
        self::assertIsResource($stream);
        fwrite($stream, 'streamed');
        rewind($stream);

        $item = $this->subject->convert(self::legacyRow(['data' => $stream]));

        self::assertNotNull($item);
        self::assertSame(QueueItem::encode(['data' => 'streamed']), $item['data']);
    }

    /**
     * @return array<string, array{int|string, QueueItemStatus}>
     */
    public static function statusDataProvider(): array
    {
        return [
            'new' => [0, QueueItemStatus::New],
            'processing becomes new' => [1, QueueItemStatus::New],
            'finished' => [2, QueueItemStatus::Finished],
            'failed' => [3, QueueItemStatus::Failed],
            'string value' => ['2', QueueItemStatus::Finished],
            'unknown value becomes failed' => [9, QueueItemStatus::Failed],
        ];
    }

    #[Test]
    #[DataProvider('statusDataProvider')]
    public function statusIsMapped(int|string $legacyStatus, QueueItemStatus $expected): void
    {
        $item = $this->subject->convert(self::legacyRow(['status' => $legacyStatus]));

        self::assertNotNull($item);
        self::assertSame($expected->value, $item['status']);
    }

    #[Test]
    public function createdDateIsKept(): void
    {
        $item = $this->subject->convert(self::legacyRow(['created_date' => 1700000000]));

        self::assertNotNull($item);
        self::assertSame(1700000000, $item['createdAt']);
    }

    #[Test]
    public function updatedIsTheLatestDate(): void
    {
        $item = $this->subject->convert(self::legacyRow([
            'created_date' => 1700000000,
            'started_date' => '1700000300',
            'finished_date' => 1700000200,
        ]));

        self::assertNotNull($item);
        self::assertSame(1700000300, $item['updatedAt']);
    }

    #[Test]
    public function missingDatesAreNull(): void
    {
        $item = $this->subject->convert(self::legacyRow());

        self::assertNotNull($item);
        self::assertNull($item['createdAt']);
        self::assertNull($item['updatedAt']);
    }

    #[Test]
    public function rowWithoutIdentifierIsNotConverted(): void
    {
        self::assertNull($this->subject->convert(self::legacyRow(['identifier' => ''])));
    }

    #[Test]
    public function rowWithDataThatIsNoUtf8IsNotConverted(): void
    {
        self::assertNull($this->subject->convert(self::legacyRow(['data' => "\xB1\x31"])));
    }
}
