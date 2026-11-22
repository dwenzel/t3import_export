<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Upgrades;

use CPSIT\ImportExportCore\Queue\QueueItem;
use CPSIT\ImportExportCore\Queue\QueueItemStatus;
use CPSIT\T3importExport\Dbal\QueueRepositoryFactory;
use CPSIT\T3importExport\Upgrades\LegacyQueueItemConverter;
use CPSIT\T3importExport\Upgrades\QueueItemMigration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Migration of the items of the legacy queue table into the queue table of
 * the core. The new table is read directly: the core repository returns the
 * timestamps as 1970-01-01 in a TYPO3 process (core 0.6.0).
 */
#[CoversClass(QueueItemMigration::class)]
#[CoversClass(LegacyQueueItemConverter::class)]
class QueueItemMigrationTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    private const string FIXTURE = __DIR__ . '/../Fixtures/legacyQueueItems.csv';

    private const string SECOND_CONNECTION = 'Second';

    private const string RENAMED_LEGACY_TABLE = 'tx_t3importexport_queueitem_renamed';

    private bool $legacyTableRenamed = false;

    #[\Override]
    protected function tearDown(): void
    {
        if ($this->legacyTableRenamed) {
            // the database is kept between the tests on MySQL
            $this->renameTable(self::RENAMED_LEGACY_TABLE, QueueItemMigration::LEGACY_TABLE);
        }
        if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION])) {
            $this->get(ConnectionPool::class)->getConnectionByName(self::SECOND_CONNECTION)->close();
            unset(
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION],
                $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][QueueRepositoryFactory::TABLE]
            );
        }
        parent::tearDown();
    }

    private function renameTable(string $from, string $to): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable(QueueItemMigration::LEGACY_TABLE);
        $connection->executeStatement(\sprintf(
            'ALTER TABLE %s RENAME TO %s',
            $connection->quoteIdentifier($from),
            $connection->quoteIdentifier($to)
        ));
    }

    /**
     * Small batches, so that the tests cover several of them.
     */
    private function createSubject(?BufferedOutput $output = null): QueueItemMigration
    {
        $subject = new QueueItemMigration($this->get(ConnectionPool::class), new LegacyQueueItemConverter(), 2);
        $subject->setOutput($output ?? new BufferedOutput());

        return $subject;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchRows(): array
    {
        return $this->get(ConnectionPool::class)
            ->getConnectionForTable(QueueRepositoryFactory::TABLE)
            ->select(
                ['queue', 'checksum', 'data', 'status', 'claim_token', 'created_at', 'updated_at'],
                QueueRepositoryFactory::TABLE,
                [],
                [],
                ['id' => 'ASC']
            )
            ->fetchAllAssociative();
    }

    /**
     * @return list<array{string, string, int}> queue, data and status of the rows
     */
    private function fetchItems(): array
    {
        return array_map(
            static fn (array $row): array => [
                (string)$row['queue'],
                (string)json_decode((string)$row['data'], true)['data'],
                (int)$row['status'],
            ],
            $this->fetchRows()
        );
    }

    private static function dateTime(int $timestamp): string
    {
        return (new \DateTimeImmutable('@' . $timestamp))
            ->setTimezone(new \DateTimeZone(date_default_timezone_get()))
            ->format('Y-m-d H:i:s');
    }

    #[Test]
    public function wizardIsRegistered(): void
    {
        $registry = $this->get(UpgradeWizardRegistry::class);

        self::assertTrue($registry->hasUpgradeWizard(QueueItemMigration::IDENTIFIER));
        self::assertInstanceOf(QueueItemMigration::class, $registry->getUpgradeWizard(QueueItemMigration::IDENTIFIER));
    }

    #[Test]
    public function updateIsNecessaryForLegacyItems(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        self::assertTrue($this->createSubject()->updateNecessary());
    }

    #[Test]
    public function updateIsNotNecessaryForEmptyLegacyTable(): void
    {
        self::assertFalse($this->createSubject()->updateNecessary());
    }

    #[Test]
    public function updateIsNotNecessaryWithoutLegacyTable(): void
    {
        $this->renameTable(QueueItemMigration::LEGACY_TABLE, self::RENAMED_LEGACY_TABLE);
        $this->legacyTableRenamed = true;

        $subject = $this->createSubject();

        self::assertFalse($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());
        self::assertSame([], $this->fetchRows());
    }

    #[Test]
    public function executeUpdateOnEmptyLegacyTableAddsNothing(): void
    {
        self::assertTrue($this->createSubject()->executeUpdate());

        self::assertSame([], $this->fetchRows());
    }

    #[Test]
    public function itemsOfAllStatusesAreMigrated(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        self::assertTrue($this->createSubject()->executeUpdate());

        self::assertSame(
            [
                ['import.foo', 'a', QueueItemStatus::New->value],
                // processing becomes new
                ['import.foo', 'b', QueueItemStatus::New->value],
                ['import.foo', 'c', QueueItemStatus::Finished->value],
                ['import.foo', 'd', QueueItemStatus::Failed->value],
                ['import.bar', 'a', QueueItemStatus::Finished->value],
                // unknown status becomes failed
                ['import.foo', 'f', QueueItemStatus::Failed->value],
            ],
            $this->fetchItems()
        );
    }

    #[Test]
    public function migratedItemsHaveTheChecksumOfTheCoreAndNoClaimToken(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->createSubject()->executeUpdate();

        foreach ($this->fetchRows() as $row) {
            $record = json_decode((string)$row['data'], true);
            self::assertIsArray($record);
            self::assertSame(QueueItem::checksum((string)$row['queue'], $record), $row['checksum']);
            self::assertSame('', $row['claim_token']);
        }
    }

    #[Test]
    public function timestampsAreMigrated(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->createSubject()->executeUpdate();

        $rows = $this->fetchRows();
        self::assertSame(self::dateTime(1700000000), $rows[0]['created_at']);
        self::assertSame(self::dateTime(1700000000), $rows[0]['updated_at']);
        self::assertSame(self::dateTime(1700000300), $rows[3]['updated_at']);
        self::assertNull($rows[5]['created_at']);
        self::assertNull($rows[5]['updated_at']);
    }

    #[Test]
    public function duplicateLegacyItemIsMigratedOnce(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $output = new BufferedOutput();

        $this->createSubject($output)->executeUpdate();

        // uid 6 has the queue and data of uid 1: the first item is kept
        $rows = array_values(array_filter(
            $this->fetchItems(),
            static fn (array $item): bool => $item[0] === 'import.foo' && $item[1] === 'a'
        ));
        self::assertSame([['import.foo', 'a', QueueItemStatus::New->value]], $rows);
        $text = $output->fetch();
        self::assertStringContainsString('6 items migrated', $text);
        self::assertStringContainsString('1 items already in the queue', $text);
    }

    #[Test]
    public function migratedItemsAreDuplicatesForTheCoreQueue(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->createSubject()->executeUpdate();
        $repository = $this->get(QueueRepositoryFactory::class)->create();

        self::assertFalse($repository->enqueue('import.foo', ['data' => 'c']));
        self::assertTrue($repository->enqueue('import.foo', ['data' => 'new']));
        self::assertSame(1, $repository->count('import.foo', QueueItemStatus::Finished));
    }

    #[Test]
    public function rowsWithoutIdentifierOrValidDataAreSkippedAndReported(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->get(ConnectionPool::class)
            ->getConnectionForTable(QueueItemMigration::LEGACY_TABLE)
            ->insert(QueueItemMigration::LEGACY_TABLE, ['uid' => 9, 'identifier' => 'import.foo', 'data' => "\xB1\x31"]);
        $output = new BufferedOutput();
        $subject = $this->createSubject($output);

        self::assertTrue($subject->executeUpdate());

        self::assertCount(6, $this->fetchRows());
        $text = $output->fetch();
        self::assertStringContainsString('2 items skipped', $text);
        self::assertStringContainsString('7, 9', $text);
        self::assertFalse($subject->updateNecessary());
    }

    #[Test]
    public function updateIsNotNecessaryAfterMigration(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $subject = $this->createSubject();

        $subject->executeUpdate();

        self::assertFalse($subject->updateNecessary());
    }

    #[Test]
    public function secondRunAddsNoRows(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->createSubject()->executeUpdate();
        $expected = $this->fetchRows();
        $output = new BufferedOutput();

        self::assertTrue($this->createSubject($output)->executeUpdate());

        self::assertSame($expected, $this->fetchRows());
        $text = $output->fetch();
        self::assertStringContainsString('0 items migrated', $text);
        self::assertStringContainsString('7 items already in the queue', $text);
    }

    #[Test]
    public function existingItemsOfTheCoreQueueAreNotChanged(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $repository = $this->get(QueueRepositoryFactory::class)->create();
        $repository->enqueue('import.foo', ['data' => 'a']);
        $repository->complete($repository->claim('import.foo', 1)[0]);

        $this->createSubject()->executeUpdate();

        $items = $this->fetchItems();
        self::assertSame(['import.foo', 'a', QueueItemStatus::Finished->value], $items[0]);
        self::assertCount(6, $items);
    }

    #[Test]
    public function updateIsNecessaryForLegacyItemsAddedAfterMigration(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $subject = $this->createSubject();
        $subject->executeUpdate();
        $this->get(ConnectionPool::class)
            ->getConnectionForTable(QueueItemMigration::LEGACY_TABLE)
            ->insert(QueueItemMigration::LEGACY_TABLE, ['uid' => 10, 'identifier' => 'import.foo', 'data' => 'g']);

        self::assertTrue($subject->updateNecessary());
        $subject->executeUpdate();
        self::assertCount(7, $this->fetchRows());
        self::assertFalse($subject->updateNecessary());
    }

    #[Test]
    public function legacyTableIsNotChanged(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->createSubject()->executeUpdate();

        $this->assertCSVDataSet(self::FIXTURE);
    }

    #[Test]
    public function itemsAreWrittenToTheConnectionOfTheTableMapping(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION]
            = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME];
        $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][QueueRepositoryFactory::TABLE] = self::SECOND_CONNECTION;
        $second = $this->get(ConnectionPool::class)->getConnectionByName(self::SECOND_CONNECTION);

        // `Second` points to the same database as `Default`; that a rollback
        // of `Second` removes the items proves that the wizard uses it
        $second->beginTransaction();
        $this->createSubject()->executeUpdate();
        self::assertCount(6, $this->fetchRows());
        $second->rollBack();

        self::assertSame([], $this->fetchRows());
    }
}
