<?php

namespace CPSIT\T3importExport\Domain\Repository;

use CPSIT\T3importExport\Domain\Model\ImportTask;
use CPSIT\T3importExport\Domain\Model\Queue;
use TYPO3\CMS\Extbase\Persistence\Repository;


class QueueRepository extends Repository
{
    static protected $transaction = false;

    /**
     * @param \CPSIT\T3importExport\Persistence\PersistenceManager $persistenceManager
     */
    public function injectPersistenceManager(\CPSIT\T3importExport\Persistence\PersistenceManager $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

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

        if (isset($queueConfig['importBatchSize'])) {
            $queue->setBatchSize((int)$queueConfig['importBatchSize']);
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
    public function hasQueueForTask(ImportTask $task, $pid)
    {
        $query = $this->createQuery();
        //$query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setStoragePageIds(array($pid));
        $query->matching($query->equals('taskIdentifier', $task->getIdentifier()));
        $query->setLimit(1);
        $result = (int)$query->execute()->count();

        return (bool)($result === 1);
    }

    public function getQueueForTask(ImportTask $task, $pid)
    {
        $query = $this->createQuery();
        //$query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setStoragePageIds(array($pid));
        $query->matching($query->equals('taskIdentifier', $task->getIdentifier()));
        $query->setLimit(1);
        $result = $query->execute();

        return $result->getFirst();
    }

    /**
     * @param Queue $queue
     */
    public function persist(Queue $queue, $flush = false)
    {
        // write the current queue and queueItems into the Database
        // than clear the queue buffer with unset and re-init - if flush true
        if ($flush) {
            // if null given the queueItem will be removed from memory and re-init a new ObjectStorage
            $queue->setQueueItems(null);
        }
    }

    public function persistAll()
    {
        $this->persistenceManager->persistAll();
    }

    public function isTransactionActive()
    {
        return self::$transaction;
    }
}
