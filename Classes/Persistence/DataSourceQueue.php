<?php

namespace CPSIT\T3importExport\Persistence;

use CPSIT\T3importExport\ConfigurableTrait;
use CPSIT\T3importExport\Domain\Model\QueueItem;
use CPSIT\T3importExport\Domain\Repository\QueueItemRepository;
use CPSIT\T3importExport\Persistence\Query\QueryInterface;
use CPSIT\T3importExport\Persistence\Query\SelectQuery;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
 * Class DataTargetQueue
 * Creates and persists queue items
 */
class DataSourceQueue implements DataSourceInterface
{
    use ConfigurableTrait;

    public const KEY_IDENTIFIER = 'identifier';
    public const KEY_BATCH_SIZE = 'batchSize';
    public const DEFAULT_BATCH_SIZE = 10;
    protected string $targetClass = QueueItem::class;

    protected QueueItemRepository $repository;

    public function __construct(QueueItemRepository $repository = null)
    {
        $this->repository = $repository ?? (GeneralUtility::makeInstance(QueueItemRepository::class));
    }

    public function isConfigurationValid(array $configuration): bool
    {
        if (
            empty($configuration[self::KEY_IDENTIFIER])
            || !is_string($configuration[self::KEY_IDENTIFIER])) {
            return false;
        }
        $parts = explode('.', $configuration[self::KEY_IDENTIFIER]);

        // identifier mus begin with either 'import.' or 'export.'
        if (!($parts[0] === 'import' || $parts === 'export')) {
            return false;
        }

        if (!empty($configuration[self::KEY_BATCH_SIZE])
            && !MathUtility::canBeInterpretedAsInteger($configuration[self::KEY_BATCH_SIZE])
        ) {
            return false;
        }
        return true;
    }

    public function getRecords(array $configuration)
    {
        $identifier = $configuration[self::KEY_IDENTIFIER];
        $limit = static::DEFAULT_BATCH_SIZE;
        if (!empty($configuration[self::KEY_BATCH_SIZE])) {
            $limit = (int)$configuration[self::KEY_BATCH_SIZE];
        }

        $fields = [QueueItem::FIELD_UID, QueueItem::FIELD_DATA];
        return $this->repository->findNewByIdentifier($identifier, $limit, $fields);
    }
}
