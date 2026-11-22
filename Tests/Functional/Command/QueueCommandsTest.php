<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Command;

use CPSIT\ImportExportCore\Queue\QueueItemStatus;
use CPSIT\ImportExportCore\Queue\QueueRepositoryInterface;
use CPSIT\T3importExport\Command\QueueReleaseCommand;
use CPSIT\T3importExport\Command\QueueStatusCommand;
use CPSIT\T3importExport\Dbal\QueueRepositoryFactory;
use CPSIT\T3importExport\Dbal\QueueStatusReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The commands queue:status and queue:release on the queue table of the
 * core.
 */
#[CoversClass(QueueStatusCommand::class)]
#[CoversClass(QueueReleaseCommand::class)]
#[CoversClass(QueueStatusReader::class)]
class QueueCommandsTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    private QueueRepositoryInterface $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->get(QueueRepositoryInterface::class);
        // import.foo: 1 finished, 2 processing, 1 new; export.bar: 1 failed, 1 processing
        foreach (['a', 'b', 'c', 'd'] as $data) {
            $this->repository->enqueue('import.foo', ['data' => $data]);
        }
        $this->repository->complete($this->repository->claim('import.foo', 1)[0]);
        $this->repository->claim('import.foo', 2);
        $this->repository->enqueue('export.bar', ['data' => 'e']);
        $this->repository->enqueue('export.bar', ['data' => 'f']);
        $this->repository->fail($this->repository->claim('export.bar', 1)[0]);
        $this->repository->claim('export.bar', 1);
    }

    private function createTester(string $name): CommandTester
    {
        return new CommandTester($this->get(CommandRegistry::class)->getCommandByIdentifier($name));
    }

    /**
     * Counts the rows of the item list: ID, queue, status, created, updated.
     * The times are not checked.
     */
    private static function countItemRows(string $display, string $queue, string $status): int
    {
        return (int)preg_match_all(
            '/^\|\s+\d+\s+\|\s+' . $queue . '\s+\|\s+' . $status . '\s+\|[^|]+\|[^|]+\|$/m',
            $display
        );
    }

    #[Test]
    public function commandsAreRegisteredWithAliases(): void
    {
        $registry = $this->get(CommandRegistry::class);

        self::assertInstanceOf(QueueStatusCommand::class, $registry->getCommandByIdentifier('t3import-export:queue:status'));
        self::assertInstanceOf(QueueStatusCommand::class, $registry->getCommandByIdentifier('queue:status'));
        self::assertInstanceOf(QueueReleaseCommand::class, $registry->getCommandByIdentifier('t3import-export:queue:release'));
        self::assertInstanceOf(QueueReleaseCommand::class, $registry->getCommandByIdentifier('queue:release'));
    }

    #[Test]
    public function statusListsTheCountsByStatusOfAllQueues(): void
    {
        $tester = $this->createTester(QueueStatusCommand::DEFAULT_NAME);

        self::assertSame(Command::SUCCESS, $tester->execute([]));

        $display = $tester->getDisplay();
        self::assertMatchesRegularExpression('/export\.bar\s+\|\s+0\s+\|\s+1\s+\|\s+0\s+\|\s+1\s+\|\s+2\s/', $display);
        self::assertMatchesRegularExpression('/import\.foo\s+\|\s+1\s+\|\s+2\s+\|\s+1\s+\|\s+0\s+\|\s+4\s/', $display);
        self::assertLessThan(strpos($display, 'import.foo'), strpos($display, 'export.bar'));
        self::assertStringNotContainsString('Created', $display);
    }

    #[Test]
    public function statusWithQueueListsItsItems(): void
    {
        $tester = $this->createTester(QueueStatusCommand::DEFAULT_NAME);

        self::assertSame(Command::SUCCESS, $tester->execute(['--queue' => ['import.foo']]));

        $display = $tester->getDisplay();
        self::assertStringNotContainsString('export.bar', $display);
        self::assertMatchesRegularExpression('/import\.foo\s+\|\s+1\s+\|\s+2\s+\|\s+1\s+\|\s+0\s+\|\s+4\s/', $display);
        self::assertStringContainsString('Created', $display);
        self::assertStringContainsString('Updated', $display);
        self::assertSame(1, self::countItemRows($display, 'import.foo', 'finished'));
        self::assertSame(2, self::countItemRows($display, 'import.foo', 'processing'));
        self::assertSame(1, self::countItemRows($display, 'import.foo', 'new'));
        self::assertSame(4, self::countItemRows($display, '\S+', '\S+'));
    }

    #[Test]
    public function statusWithStatusListsTheItemsWithThisStatus(): void
    {
        $tester = $this->createTester(QueueStatusCommand::DEFAULT_NAME);

        self::assertSame(Command::SUCCESS, $tester->execute(['--status' => 'processing']));

        $display = $tester->getDisplay();
        self::assertSame(2, self::countItemRows($display, 'import.foo', 'processing'));
        self::assertSame(1, self::countItemRows($display, 'export.bar', 'processing'));
        self::assertSame(3, self::countItemRows($display, '\S+', '\S+'));
    }

    #[Test]
    public function statusLimitsTheListOfItems(): void
    {
        $tester = $this->createTester(QueueStatusCommand::DEFAULT_NAME);

        self::assertSame(Command::SUCCESS, $tester->execute(['--status' => '1', '--limit' => '1']));

        self::assertSame(1, self::countItemRows($tester->getDisplay(), '\S+', 'processing'));
        self::assertStringContainsString('Showing the first 1 items', $tester->getDisplay());
    }

    #[Test]
    public function statusOfAnUnknownQueueSaysThatItIsEmpty(): void
    {
        $tester = $this->createTester(QueueStatusCommand::DEFAULT_NAME);

        self::assertSame(Command::SUCCESS, $tester->execute(['--queue' => ['import.none']]));

        self::assertStringContainsString('No queue items found.', $tester->getDisplay());
    }

    #[Test]
    public function statusRejectsAnInvalidStatus(): void
    {
        $tester = $this->createTester(QueueStatusCommand::DEFAULT_NAME);

        self::assertSame(Command::INVALID, $tester->execute(['--status' => 'done']));
    }

    #[Test]
    public function statusRejectsAnInvalidLimit(): void
    {
        $tester = $this->createTester(QueueStatusCommand::DEFAULT_NAME);

        self::assertSame(Command::INVALID, $tester->execute(['--status' => 'new', '--limit' => '0']));
    }

    #[Test]
    public function releaseWithQueueAndAllIsInvalid(): void
    {
        $tester = $this->createTester(QueueReleaseCommand::DEFAULT_NAME);

        self::assertSame(Command::INVALID, $tester->execute(['--queue' => ['import.foo'], '--all' => true]));
        self::assertSame(2, $this->repository->count('import.foo', QueueItemStatus::Processing));
    }

    #[Test]
    public function releaseWithAllAndNoItemsInProcessingReleasesNothing(): void
    {
        $tester = $this->createTester(QueueReleaseCommand::DEFAULT_NAME);
        $tester->execute(['--all' => true]);

        self::assertSame(Command::SUCCESS, $tester->execute(['--all' => true]));
        self::assertStringContainsString('No queue items in processing.', $tester->getDisplay());
    }

    #[Test]
    public function releaseSetsTheProcessingItemsOfAQueueBackToNew(): void
    {
        $tester = $this->createTester(QueueReleaseCommand::DEFAULT_NAME);

        self::assertSame(Command::SUCCESS, $tester->execute(['--queue' => ['import.foo']]));

        self::assertSame(0, $this->repository->count('import.foo', QueueItemStatus::Processing));
        self::assertSame(3, $this->repository->count('import.foo', QueueItemStatus::New));
        self::assertSame(1, $this->repository->count('export.bar', QueueItemStatus::Processing));
        self::assertStringContainsString('Released 2 items of queue "import.foo".', $tester->getDisplay());
    }

    #[Test]
    public function releaseWithAllReleasesTheItemsOfAllQueues(): void
    {
        $tester = $this->createTester(QueueReleaseCommand::DEFAULT_NAME);

        self::assertSame(Command::SUCCESS, $tester->execute(['--all' => true]));

        self::assertSame(0, $this->repository->count('import.foo', QueueItemStatus::Processing));
        self::assertSame(0, $this->repository->count('export.bar', QueueItemStatus::Processing));
        self::assertSame(1, $this->repository->count('export.bar', QueueItemStatus::New));
        self::assertSame(1, $this->repository->count('export.bar', QueueItemStatus::Failed));
        self::assertStringContainsString('Released 1 items of queue "export.bar".', $tester->getDisplay());
        self::assertStringContainsString('Released 2 items of queue "import.foo".', $tester->getDisplay());
    }

    #[Test]
    public function releaseWithoutQueueOrAllIsInvalid(): void
    {
        $tester = $this->createTester(QueueReleaseCommand::DEFAULT_NAME);

        self::assertSame(Command::INVALID, $tester->execute([]));
        self::assertSame(2, $this->repository->count('import.foo', QueueItemStatus::Processing));
    }

    #[Test]
    public function readerReturnsTheItemsWithTheStoredTimes(): void
    {
        $items = $this->get(QueueStatusReader::class)->findItems(['export.bar'], QueueItemStatus::Failed, 10);

        self::assertCount(1, $items);
        self::assertSame('export.bar', $items[0]['queue']);
        self::assertSame(QueueItemStatus::Failed, $items[0]['status']);
        // read from the table, not from the items of the core repository,
        // whose times are 1970-01-01 in a TYPO3 process (core#106)
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $items[0]['createdAt']);
        self::assertStringStartsNotWith('1970', $items[0]['createdAt']);
    }

    #[Test]
    public function readerListsTheItemsInTheOrderTheyWereAdded(): void
    {
        $items = $this->get(QueueStatusReader::class)->findItems([], null, 10);

        self::assertSame(
            [
                ['import.foo', QueueItemStatus::Finished],
                ['import.foo', QueueItemStatus::Processing],
                ['import.foo', QueueItemStatus::Processing],
                ['import.foo', QueueItemStatus::New],
                ['export.bar', QueueItemStatus::Failed],
                ['export.bar', QueueItemStatus::Processing],
            ],
            array_map(static fn(array $item): array => [$item['queue'], $item['status']], $items)
        );
        $ids = array_column($items, 'id');
        $sorted = $ids;
        sort($sorted);
        self::assertSame($sorted, $ids);
    }

    #[Test]
    public function itemWithAnUnknownStatusIsListedAsUnknown(): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable(QueueRepositoryFactory::TABLE)
            ->update(QueueRepositoryFactory::TABLE, ['status' => 9], ['queue' => 'export.bar', 'status' => QueueItemStatus::Failed->value]);

        $items = $this->get(QueueStatusReader::class)->findItems(['export.bar'], null, 10);
        self::assertNull($items[0]['status']);
        self::assertSame(9, $items[0]['statusValue']);

        $tester = $this->createTester(QueueStatusCommand::DEFAULT_NAME);
        self::assertSame(Command::SUCCESS, $tester->execute(['--queue' => ['export.bar']]));
        self::assertSame(1, self::countItemRows($tester->getDisplay(), 'export.bar', 'unknown \\(9\\)'));
        // the total includes the item with the unknown status
        self::assertMatchesRegularExpression('/export\.bar\s+\|\s+0\s+\|\s+1\s+\|\s+0\s+\|\s+0\s+\|\s+2\s/', $tester->getDisplay());
    }
}
