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

use CPSIT\ImportExportCore\Queue\QueueItem;
use CPSIT\ImportExportCore\Queue\QueueItemStatus;

/**
 * Converts a row of the queue table of earlier versions
 * (`tx_t3importexport_domain_model_queueitem`) into the values of the queue
 * table of the core (`tx_t3importexport_queue_item`).
 *
 * - `identifier` becomes the queue.
 * - `data` becomes the record `['data' => <data>]`, the record the queue
 *   source of earlier versions returned (without `uid`). It is stored as JSON
 *   like the core stores records, and the checksum is the one the core
 *   computes for this record (`QueueItem::checksum()`). Writing the same
 *   record into the queue again is therefore recognised as duplicate.
 * - Status: new and processing become new, finished and failed are kept.
 *   Unknown values become failed, so that the item is neither processed
 *   again nor enqueued again.
 * - `created_date` becomes `createdAt`, the latest of `created_date`,
 *   `started_date` and `finished_date` becomes `updatedAt` (timestamps, `null`
 *   if not set).
 *
 * @internal used by the upgrade wizard only
 */
final readonly class LegacyQueueItemConverter
{
    public const array STATUS_MAP = [
        0 => QueueItemStatus::New,
        1 => QueueItemStatus::New,
        2 => QueueItemStatus::Finished,
        3 => QueueItemStatus::Failed,
    ];

    /**
     * @param array<string, mixed> $row row of the legacy queue table
     * @return array{queue: string, checksum: string, data: string, status: int, createdAt: ?int, updatedAt: ?int}|null
     *   `null` if the row has no identifier or its data cannot be encoded as
     *   JSON (no UTF-8)
     */
    public function convert(array $row): ?array
    {
        $queue = $this->string($row['identifier'] ?? null);
        if ($queue === '') {
            return null;
        }
        $record = ['data' => $this->string($row['data'] ?? null)];
        try {
            $data = QueueItem::encode($record);
            $checksum = QueueItem::checksum($queue, $record);
        } catch (\JsonException) {
            return null;
        }
        $status = self::STATUS_MAP[$this->int($row['status'] ?? null)] ?? QueueItemStatus::Failed;
        $created = $this->int($row['created_date'] ?? null);
        $updated = max($created, $this->int($row['started_date'] ?? null), $this->int($row['finished_date'] ?? null));

        return [
            'queue' => $queue,
            'checksum' => $checksum,
            'data' => $data,
            'status' => $status->value,
            'createdAt' => $created > 0 ? $created : null,
            'updatedAt' => $updated > 0 ? $updated : null,
        ];
    }

    private function string(mixed $value): string
    {
        if (\is_resource($value)) {
            $value = stream_get_contents($value);
        }

        return \is_scalar($value) ? (string)$value : '';
    }

    private function int(mixed $value): int
    {
        return is_numeric($value) ? (int)$value : 0;
    }
}
