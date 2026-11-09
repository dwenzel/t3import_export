<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Persistence;

use CPSIT\ImportExportCore\ConfigurableInterface;
use CPSIT\ImportExportCore\ConfigurableTrait;
use CPSIT\ImportExportCore\Persistence\DataSourceInterface;
use CPSIT\T3importExport\Domain\Model\QueueItem;
use CPSIT\T3importExport\Domain\Repository\QueueItemRepository;
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
 * Class DataSourceQueue
 * Reads new queue items and marks them as processing. After the task
 * they are marked as finished or failed (see CompletableSourceInterface).
 */
class DataSourceQueue implements DataSourceInterface, ConfigurableInterface, CompletableSourceInterface
{
    use ConfigurableTrait;

    final public const string KEY_IDENTIFIER = 'identifier';
    final public const string KEY_BATCH_SIZE = 'batchSize';
    final public const int DEFAULT_BATCH_SIZE = 10;
    protected string $targetClass = QueueItem::class;

    /**
     * uids of the items claimed by getRecords()
     *
     * @var int[]
     */
    protected array $claimedUids = [];

    public function __construct(protected QueueItemRepository $repository) {}

    public function isConfigurationValid(array $configuration): bool
    {
        if (
            empty($configuration[self::KEY_IDENTIFIER])
            || !is_string($configuration[self::KEY_IDENTIFIER])) {
            return false;
        }
        $parts = explode('.', $configuration[self::KEY_IDENTIFIER]);

        // identifier must begin with either 'import.' or 'export.'
        if (!($parts[0] === 'import' || $parts[0] === 'export')) {
            return false;
        }

        if (!empty($configuration[self::KEY_BATCH_SIZE])
            && !MathUtility::canBeInterpretedAsInteger($configuration[self::KEY_BATCH_SIZE])
        ) {
            return false;
        }
        return true;
    }

    public function getRecords(array $configuration): iterable
    {
        $identifier = $configuration[self::KEY_IDENTIFIER];
        $limit = static::DEFAULT_BATCH_SIZE;
        if (!empty($configuration[self::KEY_BATCH_SIZE])) {
            $limit = (int)$configuration[self::KEY_BATCH_SIZE];
        }

        $fields = [QueueItem::FIELD_UID, QueueItem::FIELD_DATA];
        $records = $this->repository->findNewByIdentifier($identifier, $limit, $fields);

        $uids = array_map(
            static fn(array $record): int => (int)$record[QueueItem::FIELD_UID],
            $records
        );
        $this->repository->updateStatus($uids, QueueItem::STATUS_PROCESSING);
        $this->claimedUids = array_merge($this->claimedUids, $uids);

        return $records;
    }

    public function complete(array $configuration): void
    {
        $this->release(QueueItem::STATUS_FINISHED);
    }

    public function fail(array $configuration): void
    {
        $this->release(QueueItem::STATUS_FAILED);
    }

    protected function release(int $status): void
    {
        $this->repository->updateStatus($this->claimedUids, $status);
        $this->claimedUids = [];
    }
}
