<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Dbal;

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

use CPSIT\ImportExportCore\Queue\QueueItemStatus;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * Reads the state of the queues from the queue table of the core
 * (`tx_t3importexport_queue_item`) for the command `queue:status`. The queue
 * repository of the core counts items of a single queue only and does not
 * list them.
 *
 * The times are the values stored in the table (`created_at`, `updated_at`).
 */
class QueueStatusReader
{
    public function __construct(private readonly ConnectionPool $connectionPool) {}

    /**
     * Counts the items of each queue by status.
     *
     * @param list<string> $queues only these queues; all queues if empty
     * @return array<string, array<int, int>> number of items by status value, by queue, sorted by queue
     */
    public function countByQueue(array $queues = []): array
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder
            ->select('queue', 'status')
            ->addSelectLiteral('COUNT(*) AS ' . $queryBuilder->quoteIdentifier('item_count'))
            ->from(QueueRepositoryFactory::TABLE)
            ->groupBy('queue', 'status')
            ->orderBy('queue')
            ->addOrderBy('status');
        $this->restrictQueues($queryBuilder, $queues);

        $counts = [];
        foreach ($queryBuilder->executeQuery()->fetchAllAssociative() as $row) {
            $queue = (string)$row['queue'];
            $counts[$queue] ??= array_fill_keys(array_map(static fn(QueueItemStatus $status): int => $status->value, QueueItemStatus::cases()), 0);
            $counts[$queue][(int)$row['status']] = (int)$row['item_count'];
        }

        return $counts;
    }

    /**
     * Returns the items in the order they were added.
     *
     * @param list<string> $queues only these queues; all queues if empty
     * @param QueueItemStatus|null $status only items with this status
     * @return list<array{id: int, queue: string, status: QueueItemStatus, createdAt: string, updatedAt: string}>
     */
    public function findItems(array $queues, ?QueueItemStatus $status, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder
            ->select('id', 'queue', 'status', 'created_at', 'updated_at')
            ->from(QueueRepositoryFactory::TABLE)
            ->orderBy('id')
            ->setMaxResults(max(1, $limit));
        $this->restrictQueues($queryBuilder, $queues);
        if ($status !== null) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('status', $queryBuilder->createNamedParameter($status->value, Connection::PARAM_INT))
            );
        }

        return array_map(
            static fn(array $row): array => [
                'id' => (int)$row['id'],
                'queue' => (string)$row['queue'],
                'status' => QueueItemStatus::from((int)$row['status']),
                'createdAt' => (string)$row['created_at'],
                'updatedAt' => (string)$row['updated_at'],
            ],
            $queryBuilder->executeQuery()->fetchAllAssociative()
        );
    }

    private function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(QueueRepositoryFactory::TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder;
    }

    /**
     * @param list<string> $queues
     */
    private function restrictQueues(QueryBuilder $queryBuilder, array $queues): void
    {
        if ($queues === []) {
            return;
        }
        $queryBuilder->andWhere(
            $queryBuilder->expr()->in('queue', $queryBuilder->createNamedParameter($queues, Connection::PARAM_STR_ARRAY))
        );
    }
}
