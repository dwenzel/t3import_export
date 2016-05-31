<?php

namespace CPSIT\T3importExport\Domain\Repository;

use CPSIT\T3importExport\Domain\Model\ImportTask;
use CPSIT\T3importExport\Domain\Model\Queue;
use TYPO3\CMS\Extbase\Persistence\Repository;


class QueueRepository extends Repository
{
    /**
     * Create a new Queue
     *
     * @param string $identifier
     * @param int $pid
     * @param array $queueConfig
     *
     * @return Queue
     */
    public function create($identifier, $pid, array $queueConfig = [])
    {
        /** @var Queue $queue */
        $queue = $this->objectManager->get(Queue::class);
        $queue->setTaskIdentifier($identifier);
        $queue->setPid($pid);

        // set the queue Size ... default is 0 (ZERO) - means iterate all in one way
        if (isset($queueConfig['size'])) {
            $queue->setSize((int)$queueConfig['size']);
        }

        $queue->setLockKey(
            md5(time().rand(0, 99999).$identifier).'_'.$identifier
        );
        return $queue;
    }

    /**
     * @param ImportTask $task
     * @param $pid
     * @return Queue
     */
    public function createWithTask(ImportTask $task, $pid)
    {
        return $this->create($task->getIdentifier(), $pid, $task->getQueueConfig());
    }

    /**
     * lookup Queue Table if a queue for an task already exists
     *
     * @param ImportTask $task
     * @return bool
     */
    public function hasQueueForTask(ImportTask $task)
    {
        $GLOBALS['TYPO3_DB']->store_lastBuiltQuery = 1;
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        // $querySettings->setStoragePageIds(array(1, 26, 989));
        $query->matching($query->equals('taskIdentifier', $task->getIdentifier()));
        $query->setLimit(1);
        $result = (int)$query->execute()->count();

        return (bool)($result === 1);
    }
}
