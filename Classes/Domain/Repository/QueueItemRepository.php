<?php

namespace CPSIT\T3importExport\Domain\Repository;

use CPSIT\T3importExport\Domain\Model\QueueItem;
use TYPO3\CMS\Extbase\Persistence\Repository;


class QueueItemRepository extends Repository
{
    /**
     * @param int $index
     * @param int $pid
     * @return QueueItem
     */
    public function createWithIndex($index, $pid)
    {
        /** @var QueueItem $queueItem */
        $queueItem = $this->objectManager->get(QueueItem::class);
        $queueItem->setPid($pid);
        $queueItem->setDataSourceIndex((int)$index);
        return $queueItem;
    }
}
