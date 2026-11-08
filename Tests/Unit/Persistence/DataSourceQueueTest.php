<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Domain\Repository\QueueItemRepository;
use CPSIT\T3importExport\Persistence\DataSourceQueue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DataSourceQueueTest extends TestCase
{
    protected DataSourceQueue $subject;

    protected function setUp(): void
    {
        $repository = $this->getMockBuilder(QueueItemRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = new DataSourceQueue($repository);
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
}
