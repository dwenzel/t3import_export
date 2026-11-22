<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Persistence;

use CPSIT\ImportExportCore\Domain\Model\ResultInterface;
use CPSIT\ImportExportCore\Persistence\DataSourceQueue as CoreDataSourceQueue;
use CPSIT\ImportExportCore\Queue\QueueItem;
use CPSIT\T3importExport\Utility\DeprecationUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2022 Dirk Wenzel <wenzel@cps-it.de>
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

/**
 * Reads records from the queue of the core (table
 * `tx_t3importexport_queue_item`) and claims them. After the task the claimed
 * items are marked as finished, or as failed if the task fails.
 *
 * Adapter on the queue source of the core, which accepts the options of
 * earlier versions:
 *
 * - `identifier` (string, required): the queue
 * - `limit` (int, default 10): items per run
 * - `batchSize` (deprecated): used as `limit` if `limit` is not set
 *
 * The records are those written into the queue, e.g. `['data' => '...']`
 * for records of `DataTargetQueue`. They have no `uid`.
 */
class DataSourceQueue extends CoreDataSourceQueue implements CompletableSourceInterface
{
    /**
     * @deprecated since 4.0, will be removed in 5.0. Use `limit` (KEY_LIMIT).
     */
    final public const string KEY_BATCH_SIZE = 'batchSize';

    /**
     * @deprecated since 4.0, will be removed in 5.0. Use DEFAULT_LIMIT.
     */
    final public const int DEFAULT_BATCH_SIZE = self::DEFAULT_LIMIT;

    public function isConfigurationValid(array $configuration): bool
    {
        return parent::isConfigurationValid($this->normalizeConfiguration($configuration, false));
    }

    public function getRecords(array $configuration): iterable
    {
        return parent::getRecords($this->normalizeConfiguration($configuration, true));
    }

    /**
     * Items marked as failed since the last `complete()`
     *
     * @var list<QueueItem>
     */
    protected array $failed = [];

    /**
     * Marks the claimed items as failed. The argument is optional, since
     * `TransferService` calls `fail()` without an error
     * (`CompletableSourceInterface` of the extension).
     *
     * A later call marks the same items as failed again: the processor of the
     * core tells the source before the target, so a target that writes on the
     * same connection (e.g. `DataTargetDB`) rolls back the failed status with
     * its batch. `TransferService` calls `fail()` again after the processor.
     */
    public function fail(?\Throwable $error = null): void
    {
        $this->failed = [...$this->failed, ...$this->claimed];
        $this->claimed = $this->failed;
        parent::fail($error ?? new \RuntimeException('The task failed.', 1_796_400_101));
    }

    public function complete(ResultInterface $result): void
    {
        $this->failed = [];
        parent::complete($result);
    }

    /**
     * Maps `batchSize` onto `limit`: an integer or a string that can be
     * interpreted as integer. An empty `batchSize` stands for the default.
     *
     * @param array<array-key, mixed> $configuration
     * @return array<array-key, mixed>
     */
    protected function normalizeConfiguration(array $configuration, bool $notify): array
    {
        if (!array_key_exists(self::KEY_BATCH_SIZE, $configuration)) {
            return $configuration;
        }
        $batchSize = $configuration[self::KEY_BATCH_SIZE];
        unset($configuration[self::KEY_BATCH_SIZE]);
        if ($notify) {
            DeprecationUtility::trigger(
                'The option "batchSize" of DataSourceQueue is deprecated since 4.0 and will be removed in 5.0. Use "limit".',
                DeprecationUtility::DATABASE_ISSUE_URL
            );
        }
        if (array_key_exists(self::KEY_LIMIT, $configuration) || empty($batchSize)) {
            return $configuration;
        }
        $configuration[self::KEY_LIMIT] = MathUtility::canBeInterpretedAsInteger($batchSize) ? (int)$batchSize : $batchSize;

        return $configuration;
    }
}
