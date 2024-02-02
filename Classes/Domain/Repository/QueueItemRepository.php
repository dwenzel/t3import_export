<?php

namespace CPSIT\T3importExport\Domain\Repository;

use CPSIT\T3importExport\DatabaseTrait;
use CPSIT\T3importExport\Domain\Model\QueueItem;
use CPSIT\T3importExport\Exception\InvalidArgumentException;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;

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
class QueueItemRepository
{
    use DatabaseTrait;

    final public const TEMPLATE_QUEUE_ITEM = [
        QueueItem::FIELD_STATUS => QueueItem::STATUS_NEW,
        QueueItem::FIELD_IDENTIFIER => '',
        QueueItem::FIELD_CHECKSUM => '',
        QueueItem::FIELD_DATA => '',
        QueueItem::FIELD_CREATED => '',
    ];

    final public const INVALID_TYPE_MESSAGE = 'Expected instance of %s got %s.';
    final public const INVALID_TYPE_CODE = 1_644_582_032;


    /**
     * Constructor
     * @param ConnectionPool|null $connectionPool
     */
    public function __construct(ConnectionPool $connectionPool = null)
    {
        $this->connectionPool = $connectionPool ?? GeneralUtility::makeInstance(ConnectionPool::class);
    }


    /**
     * Creates a new queue record from given data
     *
     * @param $record
     * @return array
     */
    public function fromRecord($record): array
    {
        $item = self::TEMPLATE_QUEUE_ITEM;

        foreach ($item as $field => $value) {
            if (empty($record[$field])) {
                continue;
            }

            $item[$field] = $record[$field];
        }

        return $item;
    }

    /**
     * updates an existing queue record.
     * The record will be identified either by uid or by both fields `idendifier` and `checksum`
     *
     * @param array $item
     * @return bool
     * @throws InvalidArgumentException
     */
    public function update(array $item): bool
    {
        $this->assertIdentifiableRecord($item)
            ->assertExistingRecord($item);

        $data = $item;

        $identifiers = $this->determineIdentifiers($data);
        // make sure uid is not updated
        if (!empty($identifiers[QueueItem::FIELD_UID])) {
            unset($data[QueueItem::FIELD_UID]);
        }
        return (bool)$this->connectionPool->getConnectionForTable(QueueItem::TABLE)
            ->update(
                QueueItem::TABLE,
                $data,
                $identifiers
            );
    }

    /**
     * @param array $item
     * @throws InvalidArgumentException
     */
    protected function assertExistingRecord(array $item): self
    {
        if ($this->isNew($item)) {
            $message = 'Cannot update or remove record. It is new.';
            throw new InvalidArgumentException(
                $message,
                1644911540
            );
        }

        return $this;
    }

    /**
     * Tells if a record already exist. This is the case,
     * either if it has a uid or if a record with the same checksum and identifier
     * can be found in the database
     * @param array $item
     * @return bool
     */
    public function isNew(array $item): bool
    {
        $identifiers = [];
        if ($this->canIdentify($item)) {
            $identifiers = $this->determineIdentifiers($item);
        }

        if (
            //whether uid exists (which is extremely identified)
            !empty($item[QueueItem::FIELD_UID])
            ||
            //or you have a checksum and an identifier (task name)
            (!empty($identifiers[QueueItem::FIELD_CHECKSUM])
                && !empty($identifiers[QueueItem::FIELD_IDENTIFIER]))
        ) {

            return !(bool)$this->connectionPool->getConnectionForTable(QueueItem::TABLE)
                ->count(
                    'uid',
                    QueueItem::TABLE,
                    $identifiers
                );
        }

        return true;
    }

    protected function canIdentify(array $item)
    {
        return !empty($this->determineIdentifiers($item));
    }

    /**
     * @param array $item
     * @return array
     */
    protected function determineIdentifiers(array $item): array
    {
        $identifiers = [];
        if (!empty($item[QueueItem::FIELD_UID])) {
            $identifiers = [
                QueueItem::FIELD_UID => $item[QueueItem::FIELD_UID]
            ];
        }

        if (
            empty($item[QueueItem::FIELD_UID])
            && !empty($item[QueueItem::FIELD_IDENTIFIER])
            && !empty($item[QueueItem::FIELD_CHECKSUM])
        ) {
            $identifiers = [
                QueueItem::FIELD_IDENTIFIER => $item[QueueItem::FIELD_IDENTIFIER],
                QueueItem::FIELD_CHECKSUM => $item[QueueItem::FIELD_CHECKSUM]
            ];
        }

        return $identifiers;
    }

    /**
     * @param array $item
     * @throws InvalidArgumentException
     */
    protected function assertIdentifiableRecord(array $item): self
    {
        if (!$this->canIdentify($item)) {
            $message = 'Cannot identify record.';
            throw new InvalidArgumentException(
                $message,
                1644911538
            );
        }

        return $this;
    }

    /**
     * @param $item
     * @return bool
     * @throws InvalidArgumentException
     */
    public function add(array $item): bool
    {
        $this->assertNewRecord($item)
            ->assertIdentifiableRecord($item)
            ->assertValidRecord($item);

        return (bool)$this->connectionPool->getConnectionForTable(QueueItem::TABLE)
            ->insert(QueueItem::TABLE, $item);
    }

    /**
     * @param array $item
     * @throws InvalidArgumentException
     */
    protected function assertValidRecord(array $item): self
    {
        if (!$this->isValid($item)) {
            $message = 'Record is invalid.';
            throw new InvalidArgumentException(
                $message,
                1644911541
            );
        }

        return $this;
    }

    protected function isValid($queueItem): bool
    {
        return (
            !empty($queueItem[QueueItem::FIELD_IDENTIFIER])
            && isset($queueItem[QueueItem::FIELD_DATA])
        );
    }

    /**
     * @param array $item
     * @throws InvalidArgumentException
     */
    protected function assertNewRecord(array $item): self
    {
        if (!$this->isNew($item)) {
            $message = 'Cannot add record. It is not new.';
            throw new InvalidArgumentException(
                $message,
                1644911539
            );
        }

        return $this;
    }

    /**
     * Removes an existing record. The record can be identified either by
     * its `uid` or by `identifier` and `checksum`
     *
     * @param array $item
     * @throws InvalidArgumentException
     */
    public function remove(array $item): bool
    {
        $this->assertExistingRecord($item);

        $identifiers = $this->determineIdentifiers($item);
        return (bool)$this->connectionPool->getConnectionForTable(QueueItem::TABLE)
            ->delete(
                QueueItem::TABLE,
                $identifiers
            );
    }

    public function findAll()
    {
        // TODO: Implement findAll() method.
    }

    public function countAll()
    {
        // TODO: Implement countAll() method.
    }

    public function removeAll()
    {
        // TODO: Implement removeAll() method.
    }

    /**
     * @param string $identifier
     * @param int $limit
     * @param string $fields
     * @return array
     */
    public function findNewByIdentifier(string $identifier, int $limit = 10, array $fields = ['*']): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(QueueItem::TABLE);

        $query =  $queryBuilder
            ->select(...$fields)
            ->from(QueueItem::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    QueueItem::FIELD_IDENTIFIER,
                    $queryBuilder->createNamedParameter($identifier)
                ),
                $queryBuilder->expr()->eq(
                    QueueItem::FIELD_STATUS,
                    $queryBuilder->createNamedParameter(QueueItem::STATUS_NEW, PDO::PARAM_INT)
                )
            )
            ->setMaxResults($limit);
            $result = $query->execute()
            ->fetchAllAssociative();

        return is_array($result)? $result : [];
    }

    public function setDefaultOrderings(array $defaultOrderings)
    {
        // TODO: Implement setDefaultOrderings() method.
    }

    public function setDefaultQuerySettings(QuerySettingsInterface $defaultQuerySettings)
    {
        // TODO: Implement setDefaultQuerySettings() method.
    }
}
