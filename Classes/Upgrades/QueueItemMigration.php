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
 * already in the queue of the core (same queue and checksum) are not changed;
 * of several legacy items with the same queue and data, the first one (lowest
 * `uid`) is copied. Running the wizard again therefore adds only the items
 * that are missing. The legacy table is read but not changed.
 *
 * The tables are read and written in batches on the connections TYPO3 assigns
 * to them (table mapping), which may differ.
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
        . 'changed.';

    /**
     * @var string[]
     */
    public const array PRE_REQUISITES = [
        DatabaseUpdatedPrerequisite::class,
    ];

    public const string LEGACY_TABLE = 'tx_t3importexport_domain_model_queueitem';

    public const int DEFAULT_BATCH_SIZE = 500;

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
        foreach ($this->readBatches() as $batch) {
            if ($this->findMissing($batch['items']) !== []) {
                return true;
            }
        }

        return false;
    }

    public function executeUpdate(): bool
    {
        $migrated = 0;
        $existing = 0;
        $skipped = [];
        foreach ($this->readBatches() as $batch) {
            $skipped = [...$skipped, ...$batch['skipped']];
            $missing = $this->findMissing($batch['items']);
            $existing += \count($batch['items']) - \count($missing);
            $migrated += $this->insert($missing);
        }

        $this->output->writeln(\sprintf('%d items migrated.', $migrated));
        $this->output->writeln(\sprintf('%d items already in the queue of the core.', $existing));
        if ($skipped !== []) {
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
     * @return \Generator<array{items: list<array{queue: string, checksum: string, data: string, status: int, createdAt: ?int, updatedAt: ?int}>, skipped: list<int>}>
     */
    private function readBatches(): \Generator
    {
        if (!$this->legacyTableExists()) {
            return;
        }
        $lastUid = 0;
        do {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::LEGACY_TABLE);
            $queryBuilder->getRestrictions()->removeAll();
            $rows = $queryBuilder
                ->select(...self::LEGACY_FIELDS)
                ->from(self::LEGACY_TABLE)
                ->where($queryBuilder->expr()->gt('uid', $queryBuilder->createNamedParameter($lastUid, Connection::PARAM_INT)))
                ->orderBy('uid')
                ->setMaxResults($this->batchSize)
                ->executeQuery()
                ->fetchAllAssociative();

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
     * Returns the items that are not in the queue of the core yet, each queue
     * and checksum once (the first item).
     *
     * @param list<array{queue: string, checksum: string, data: string, status: int, createdAt: ?int, updatedAt: ?int}> $items
     * @return list<array{queue: string, checksum: string, data: string, status: int, createdAt: ?int, updatedAt: ?int}>
     */
    private function findMissing(array $items): array
    {
        if ($items === []) {
            return [];
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(QueueRepositoryFactory::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('queue', 'checksum')
            ->from(QueueRepositoryFactory::TABLE)
            ->where($queryBuilder->expr()->in(
                'checksum',
                $queryBuilder->createNamedParameter(
                    array_values(array_unique(array_column($items, 'checksum'))),
                    ArrayParameterType::STRING
                )
            ))
            ->executeQuery()
            ->fetchAllAssociative();
        $known = [];
        foreach ($rows as $row) {
            $known[self::key((string)$row['queue'], (string)$row['checksum'])] = true;
        }

        $missing = [];
        foreach ($items as $item) {
            $key = self::key($item['queue'], $item['checksum']);
            if (isset($known[$key])) {
                continue;
            }
            $known[$key] = true;
            $missing[] = $item;
        }

        return $missing;
    }

    /**
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
        $rows = array_map(
            static fn(array $item): array => [
                $item['queue'],
                $item['checksum'],
                $item['data'],
                $item['status'],
                '',
                self::formatDateTime($item['createdAt'], $format),
                self::formatDateTime($item['updatedAt'], $format),
            ],
            $items
        );

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
