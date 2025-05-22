<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Persistence;

use CPSIT\ImportExportCore\ConfigurableInterface;
use CPSIT\ImportExportCore\ConfigurableTrait;
use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\ImportExportCore\Persistence\DataTargetInterface;
use CPSIT\T3importExport\Domain\Model\QueueItem;
use CPSIT\T3importExport\Domain\Repository\QueueItemRepository;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

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
class DataTargetQueue implements DataTargetInterface, ConfigurableInterface
{
    use ConfigurableTrait;

    final public const string KEY_IDENTIFIER = 'identifier';
    final public const string KEY_ALLOW_UPDATE = 'allowUpdate';
    protected string $targetClass = QueueItem::class;

    public function __construct(protected QueueItemRepository $repository) {}

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

        if (!empty($configuration[self::KEY_ALLOW_UPDATE])
            && !is_string($configuration[self::KEY_ALLOW_UPDATE])) {
            return false;
        }
        return true;
    }

    /**
     * Persist QueueItems
     * Note: Only arrays representing a valid QueueItem record are considered.
     * Instances of DomainObjectInterface are ignored.
     *
     * @param array|DomainObjectInterface $object
     * @return bool|mixed
     */
    public function persist($object, ?array $configuration = null)
    {
        $result = null;
        if (!is_array($object)) {
            // todo log warning
            return false;
        }
        if (empty($object[self::KEY_IDENTIFIER])) {
            $object[QueueItem::FIELD_IDENTIFIER] = $configuration[self::KEY_IDENTIFIER];
            $object[QueueItem::FIELD_CHECKSUM] = sha1($object[QueueItem::FIELD_DATA] . $configuration[self::KEY_IDENTIFIER]);
        }

        try {
            if ($this->repository->isNew($object)) {
                $result = $this->repository->add($object);
            } elseif ((bool)$configuration[self::KEY_ALLOW_UPDATE]) {
                $result = $this->repository->update($object);
            }
        } catch (InvalidArgumentException) {
            // todo log error
            $result = false;
        }

        return $result;
    }

    /**
     * Method doesn't do anything.
     * Please @see DataTargetQueue::persist
     *
     * @param null $result
     * @return mixed|void
     */
    public function persistAll($result = null, ?array $configuration = null) {}
}
