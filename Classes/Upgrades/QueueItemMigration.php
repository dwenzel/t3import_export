<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Upgrades;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use CPSIT\T3importExport\Dbal\QueueRepositoryFactory;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ParameterType;
use DWenzel\T3extensionTools\Traits\Upgrade\UpgradeWizardTrait;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Upgrades\ChattyInterface;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;

/**
 * Copies the items of the queue table of earlier versions
 * (`tx_t3importexport_domain_model_queueitem`) into the queue table of the
 * core (`tx_t3importexport_queue_item`). See `LegacyQueueItemConverter` for
 * the mapping of the fields and statuses.
 *
 * Items of all statuses are copied, so that records that were processed
 * before are recognised as duplicates by the core queue. Items that are
 * already in the queue of the core (same queue and checksum) are not changed.
 * Of several legacy items with the same queue and data, a pending one (new or
 * processing) is preferred, then the lowest `uid`: pending items are copied
 * in a first pass, the others in a second one. Running the wizard again
 * therefore adds only the items that are missing. The legacy table is read
 * but not changed.
 *
 * The tables are read in batches and written in chunks on the connections
 * TYPO3 assigns to them (table mapping), which may differ.
 */
#[UpgradeWizard(QueueItemMigration::IDENTIFIER)]
final class QueueItemMigration implements UpgradeWizardInterface, ChattyInterface
{
    use UpgradeWizardTrait;

    public const string IDENTIFIER = 't3importExport_queueItemMigration';

    public const string TITLE = 'Import/Export: migrate queue items to the queue of import-export-core';

    public const string DESCRIPTION = 'Copies the items of the queue table tx_t3importexport_domain_model_queueitem '
        . 'into the queue table tx_t3importexport_queue_item of cpsit/import-export-core. Items of all statuses are '
        . 'copied, so that records processed before are recognised as duplicates; items in processing become new. '
        . 'Items already in the new table are not changed, so the wizard can be run again. The old table is not '
        . 'changed. Run it before running tasks with version 4.';

    /**
     * @var string[]
     */
    public const array PRE_REQUISITES = [
        DatabaseUpdatedPrerequisite::class,
    ];

    public const string LEGACY_TABLE = 'tx_t3importexport_domain_model_queueitem';

    public const int DEFAULT_BATCH_SIZE = 500;

    /**
     * Rows per insert statement
     */
    public const int INSERT_CHUNK_SIZE = 100;

    /**
     * Bytes of data per insert statement (approximate), below the
     * `max_allowed_packet` of MySQL and MariaDB
     */
    public const int INSERT_CHUNK_BYTES = 1048576;

    /**
     * Status values of the legacy table that are pending (new, processing)
     */
    private const array PENDING_STATUSES = [0, 1];

    private const array LEGACY_FIELDS = [
        'uid',
        'identifier',
        'data',
        'status',
        'created_date',
        'started_date',
        'finished_date',
    ];

    private const array COLUMNS = ['queue', 'checksum', 'data', 'status', 'claim_token', 'created_at', 'updated_at'];

    private const array COLUMN_TYPES = [
        ParameterType::STRING,
        ParameterType::STRING,
        ParameterType::STRING,
        ParameterType::INTEGER,
        ParameterType::STRING,
        ParameterType::STRING,
        ParameterType::STRING,
    ];

    private OutputInterface $output;

    private readonly int $batchSize;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly LegacyQueueItemConverter $converter,
        int $batchSize = self::DEFAULT_BATCH_SIZE,
    ) {
        $this->batchSize = max(1, $batchSize);
        $this->output = new NullOutput();
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Whether a legacy item is missing in the queue of the core. Reads the
     * legacy table until the first missing item is found.
     */
    public function updateNecessary(): bool
    {
        if (!$this->legacyTableExists()) {
            return false;
        }
        foreach ($this->readBatches(null) as $batch) {
            $existing = $this->findExisting($batch['items']);
            foreach ($batch['items'] as $item) {
                if (!isset($existing[self::key($item['queue'], $item['checksum'])])) {
                    return true;
                }
            }
        }

        return false;
    }

    public function executeUpdate(): bool
    {
        if (!$this->legacyTableExists()) {
            $this->output->writeln(\sprintf('Legacy table %s not found, nothing to migrate.', self::LEGACY_TABLE));

            return true;
        }
        $migrated = 0;
        $existing = 0;
        $duplicates = 0;
        $skipped = [];
        // checksums (binary) of the items inserted by this run; the checksum
        // of the core covers queue and data
        $inserted = [];
        foreach ([true, false] as $pending) {
            foreach ($this->readBatches($pending) as $batch) {
                $skipped = [...$skipped, ...$batch['skipped']];
                $known = $this->findExisting($batch['items']);
                $missing = [];
                foreach ($batch['items'] as $item) {
                    $checksum = (string)hex2bin($item['checksum']);
                    if (isset($inserted[$checksum])) {
                        $duplicates++;
                    } elseif (isset($known[self::key($item['queue'], $item['checksum'])])) {
                        $existing++;
                    } else {
                        $inserted[$checksum] = true;
                        $missing[] = $item;
                    }
                }
                $count = $this->insert($missing);
                $migrated += $count;
                // rows added in the meantime by a running task
                $existing += \count($missing) - $count;
            }
        }

        $this->output->writeln(\sprintf('%d items migrated.', $migrated));
        $this->output->writeln(\sprintf('%d items already in the queue of the core.', $existing));
        $this->output->writeln(\sprintf('%d duplicate items of the legacy table not migrated (same identifier and data).', $duplicates));
        if ($skipped !== []) {
            sort($skipped);
            $this->output->writeln(\sprintf(
                '%d items skipped (no identifier or data not in UTF-8), uid: %s.',
                \count($skipped),
                implode(', ', $skipped)
            ));
        }

        return true;
    }

    /**
     * Reads the legacy table in batches ordered by uid and converts the rows.
     *
     * @param bool|null $pending true: new and processing items only, false:
     *   the other items, null: all items
     * @return \Generator<array{items: list<array{queue: string, checksum: string, data: string, status: int, createdAt: ?int, updatedAt: ?int}>, skipped: list<int>}>
     */
    private function readBatches(?bool $pending): \Generator
    {
        $lastUid = 0;
        do {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::LEGACY_TABLE);
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder
                ->select(...self::LEGACY_FIELDS)
                ->from(self::LEGACY_TABLE)
                ->where($queryBuilder->expr()->gt('uid', $queryBuilder->createNamedParameter($lastUid, Connection::PARAM_INT)))
                ->orderBy('uid')
                ->setMaxResults($this->batchSize);
            if ($pending !== null) {
                $statuses = $queryBuilder->createNamedParameter(self::PENDING_STATUSES, ArrayParameterType::INTEGER);
                $queryBuilder->andWhere(
                    $pending ? $queryBuilder->expr()->in('status', $statuses) : $queryBuilder->expr()->notIn('status', $statuses)
                );
            }
            $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

            $items = [];
            $skipped = [];
            foreach ($rows as $row) {
                $lastUid = (int)$row['uid'];
                $item = $this->converter->convert($row);
                if ($item === null) {
                    $skipped[] = $lastUid;
                    continue;
                }
                $items[] = $item;
            }
            if ($rows !== []) {
                yield ['items' => $items, 'skipped' => $skipped];
            }
        } while (\count($rows) === $this->batchSize);
    }

    private function legacyTableExists(): bool
    {
        return $this->connectionPool->getConnectionForTable(self::LEGACY_TABLE)
            ->createSchemaManager()
            ->tablesExist([self::LEGACY_TABLE]);
    }

    /**
     * Returns the keys of the items that are in the queue of the core
     * already. Queries each queue separately, so that the unique index on
     * queue and checksum is used.
     *
     * @param list<array{queue: string, checksum: string, data: string, status: int, createdAt: ?int, updatedAt: ?int}> $items
     * @return array<string, true>
     */
    private function findExisting(array $items): array
    {
        $checksums = [];
        foreach ($items as $item) {
            $checksums[$item['queue']][$item['checksum']] = true;
        }
        $existing = [];
        foreach ($checksums as $queue => $queueChecksums) {
            $queue = (string)$queue;
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable(QueueRepositoryFactory::TABLE);
            $queryBuilder->getRestrictions()->removeAll();
            $rows = $queryBuilder
                ->select('checksum')
                ->from(QueueRepositoryFactory::TABLE)
                ->where(
                    $queryBuilder->expr()->eq('queue', $queryBuilder->createNamedParameter($queue)),
                    $queryBuilder->expr()->in(
                        'checksum',
                        $queryBuilder->createNamedParameter(array_map('strval', array_keys($queueChecksums)), ArrayParameterType::STRING)
                    )
                )
                ->executeQuery()
                ->fetchFirstColumn();
            foreach ($rows as $checksum) {
                $existing[self::key($queue, (string)$checksum)] = true;
            }
        }

        return $existing;
    }

    /**
     * Inserts the items in chunks of at most INSERT_CHUNK_SIZE rows and about
     * INSERT_CHUNK_BYTES of data.
     *
     * @param list<array{queue: string, checksum: string, data: string, status: int, createdAt: ?int, updatedAt: ?int}> $items
     * @return int number of inserted rows
     */
    private function insert(array $items): int
    {
        if ($items === []) {
            return 0;
        }
        $connection = $this->connectionPool->getConnectionForTable(QueueRepositoryFactory::TABLE);
        $format = $connection->getDatabasePlatform()->getDateTimeFormatString();
        $inserted = 0;
        $chunk = [];
        $bytes = 0;
        foreach ($items as $item) {
            $chunk[] = [
                $item['queue'],
                $item['checksum'],
                $item['data'],
                $item['status'],
                '',
                self::formatDateTime($item['createdAt'], $format),
                self::formatDateTime($item['updatedAt'], $format),
            ];
            $bytes += \strlen($item['data']);
            if (\count($chunk) >= self::INSERT_CHUNK_SIZE || $bytes >= self::INSERT_CHUNK_BYTES) {
                $inserted += $this->insertChunk($connection, $chunk);
                $chunk = [];
                $bytes = 0;
            }
        }
        if ($chunk !== []) {
            $inserted += $this->insertChunk($connection, $chunk);
        }

        return $inserted;
    }

    /**
     * @param list<list<int|string|null>> $rows
     */
    private function insertChunk(Connection $connection, array $rows): int
    {
        try {
            return $connection->bulkInsert(QueueRepositoryFactory::TABLE, $rows, self::COLUMNS, self::COLUMN_TYPES);
        } catch (UniqueConstraintViolationException) {
            // an item was added in the meantime (by a running task): insert
            // the rows one by one and skip the existing ones
        }
        $inserted = 0;
        foreach ($rows as $row) {
            try {
                $inserted += $connection->insert(
                    QueueRepositoryFactory::TABLE,
                    array_combine(self::COLUMNS, $row),
                    self::COLUMN_TYPES
                );
            } catch (UniqueConstraintViolationException) {
            }
        }

        return $inserted;
    }

    /**
     * Formats the timestamp like Doctrine formats `\DateTimeImmutable` values
     * of the core queue (time zone of PHP).
     */
    private static function formatDateTime(?int $timestamp, string $format): ?string
    {
        if ($timestamp === null) {
            return null;
        }

        return (new \DateTimeImmutable('@' . $timestamp))
            ->setTimezone(new \DateTimeZone(date_default_timezone_get()))
            ->format($format);
    }

    private static function key(string $queue, string $checksum): string
    {
        return $queue . "\n" . $checksum;
    }
}
