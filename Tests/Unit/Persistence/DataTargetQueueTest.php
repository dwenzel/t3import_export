<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\ImportExportCore\Persistence\DataTargetQueue as CoreDataTargetQueue;
use CPSIT\ImportExportCore\Queue\InMemoryQueueRepository;
use CPSIT\ImportExportCore\Queue\QueueItem;
use CPSIT\ImportExportCore\Queue\QueueItemStatus;
use CPSIT\T3importExport\Persistence\DataTargetQueue;
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
#[CoversClass(DataTargetQueue::class)]
class DataTargetQueueTest extends TestCase
{
    private const string QUEUE = 'import.test';

    private const array CONFIGURATION = [
        DataTargetQueue::KEY_IDENTIFIER => self::QUEUE,
    ];

    protected DataTargetQueue $subject;
    protected InMemoryQueueRepository $repository;

    /**
     * @var list<string>
     */
    private array $deprecations = [];

    protected function setUp(): void
    {
        DeprecationUtility::reset();
        $this->repository = new InMemoryQueueRepository();
        $this->subject = new DataTargetQueue($this->repository);
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

    /**
     * @return list<array<mixed>>
     */
    private function claimAll(string $queue = self::QUEUE): array
    {
        return array_map(static fn(QueueItem $item): array => $item->data, $this->repository->claim($queue, 100));
    }

    #[Test]
    public function isAdapterOnTheQueueTargetOfTheCore(): void
    {
        self::assertInstanceOf(CoreDataTargetQueue::class, $this->subject);
    }

    public static function invalidConfigurationDataProvider(): array
    {
        return [
            'empty configuration' => [
                [],
            ],
            'identifier not set' => [
                ['foo' => 'bar'],
            ],
            'identifier not a string' => [
                [DataTargetQueue::KEY_IDENTIFIER => 1],
            ],
            'allowUpdate must not be array' => [
                [DataTargetQueue::KEY_IDENTIFIER => self::QUEUE, DataTargetQueue::KEY_ALLOW_UPDATE => []],
            ],
            'allowUpdate must not be float' => [
                [DataTargetQueue::KEY_IDENTIFIER => self::QUEUE, DataTargetQueue::KEY_ALLOW_UPDATE => 3.1],
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
                [DataTargetQueue::KEY_IDENTIFIER => 'import.foo'],
            ],
            'export identifier' => [
                [DataTargetQueue::KEY_IDENTIFIER => 'export.foo'],
            ],
            'identifier without prefix' => [
                [DataTargetQueue::KEY_IDENTIFIER => 'events'],
            ],
            'allowUpdate as boolean' => [
                [DataTargetQueue::KEY_IDENTIFIER => 'import.foo', DataTargetQueue::KEY_ALLOW_UPDATE => true],
            ],
            'allowUpdate as string' => [
                [DataTargetQueue::KEY_IDENTIFIER => 'import.foo', DataTargetQueue::KEY_ALLOW_UPDATE => '1'],
            ],
            'allowUpdate as integer' => [
                [DataTargetQueue::KEY_IDENTIFIER => 'import.foo', DataTargetQueue::KEY_ALLOW_UPDATE => 1],
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
    public function persistReturnsFalseIfObjectIsNotArray(): void
    {
        self::assertFalse($this->subject->persist(new \stdClass(), self::CONFIGURATION));
        self::assertSame([], $this->claimAll());
    }

    public static function recordWithoutValidDataProvider(): array
    {
        return [
            'no data' => [['foo' => 'bar']],
            'data null' => [['data' => null]],
            'data array' => [['data' => ['foo']]],
        ];
    }

    #[Test]
    #[DataProvider('recordWithoutValidDataProvider')]
    public function persistReturnsFalseForRecordWithoutValidData(array $record): void
    {
        self::assertFalse($this->subject->persist($record, self::CONFIGURATION));
        self::assertSame([], $this->claimAll());
    }

    #[Test]
    public function persistEnqueuesOnlyTheDataField(): void
    {
        $record = [
            'uid' => 7,
            'data' => 'test data content',
            'checksum' => 'foo',
            'status' => 2,
            'created_date' => 1700000000,
            'some_field' => 'value',
        ];

        self::assertTrue($this->subject->persist($record, self::CONFIGURATION));

        self::assertSame([['data' => 'test data content']], $this->claimAll());
    }

    #[Test]
    public function persistEnqueuesScalarDataAsString(): void
    {
        $this->subject->persist(['data' => 42], self::CONFIGURATION);

        self::assertSame([['data' => '42']], $this->claimAll());
    }

    #[Test]
    public function persistUsesTheChecksumOfTheMigratedItems(): void
    {
        $this->subject->persist(['data' => 'a'], self::CONFIGURATION);

        $item = $this->repository->claim(self::QUEUE, 1)[0];

        self::assertSame(QueueItem::checksum(self::QUEUE, ['data' => 'a']), $item->checksum);
    }

    #[Test]
    public function persistSkipsRecordsInTheQueue(): void
    {
        self::assertTrue($this->subject->persist(['data' => 'a'], self::CONFIGURATION));
        self::assertFalse($this->subject->persist(['data' => 'a', 'uid' => 3], self::CONFIGURATION));

        self::assertSame(1, $this->repository->count(self::QUEUE, QueueItemStatus::New));
    }

    #[Test]
    public function persistSetsRecordsBackToNewWithAllowUpdate(): void
    {
        $this->subject->persist(['data' => 'a'], self::CONFIGURATION);
        $this->repository->complete($this->repository->claim(self::QUEUE, 1)[0]);

        $result = $this->subject->persist(
            ['data' => 'a'],
            [DataTargetQueue::KEY_IDENTIFIER => self::QUEUE, DataTargetQueue::KEY_ALLOW_UPDATE => true]
        );

        self::assertTrue($result);
        self::assertSame(1, $this->repository->count(self::QUEUE, QueueItemStatus::New));
        self::assertSame([], $this->deprecations);
    }

    #[Test]
    public function allowUpdateAsStringIsCastAndDeprecated(): void
    {
        $this->subject->persist(['data' => 'a'], self::CONFIGURATION);
        $this->repository->complete($this->repository->claim(self::QUEUE, 1)[0]);

        $result = $this->subject->persist(
            ['data' => 'a'],
            [DataTargetQueue::KEY_IDENTIFIER => self::QUEUE, DataTargetQueue::KEY_ALLOW_UPDATE => '1']
        );

        self::assertTrue($result);
        self::assertCount(1, $this->deprecations);
        self::assertStringContainsString('allowUpdate', $this->deprecations[0]);
    }

    #[Test]
    public function allowUpdateZeroAsStringDoesNotRequeue(): void
    {
        $this->subject->persist(['data' => 'a'], self::CONFIGURATION);
        $this->repository->complete($this->repository->claim(self::QUEUE, 1)[0]);

        $result = $this->subject->persist(
            ['data' => 'a'],
            [DataTargetQueue::KEY_IDENTIFIER => self::QUEUE, DataTargetQueue::KEY_ALLOW_UPDATE => '0']
        );

        self::assertFalse($result);
        self::assertSame(1, $this->repository->count(self::QUEUE, QueueItemStatus::Finished));
    }

    #[Test]
    public function persistUsesTheConfigurationOfTheTargetWithoutConfigurationArgument(): void
    {
        $this->subject->setConfiguration(self::CONFIGURATION);

        self::assertTrue($this->subject->persist(['data' => 'a']));

        self::assertSame([['data' => 'a']], $this->claimAll());
    }

    #[Test]
    public function identifierOfTheRecordIsUsedAsQueueAndDeprecated(): void
    {
        $this->subject->persist(['identifier' => 'import.other', 'data' => 'a'], self::CONFIGURATION);

        self::assertSame([], $this->claimAll());
        self::assertSame([['data' => 'a']], $this->claimAll('import.other'));
        self::assertCount(1, $this->deprecations);
        self::assertStringContainsString('identifier', $this->deprecations[0]);
    }
}
