<?php
/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace CPSIT\T3importExport\Domain\Model;

use CPSIT\T3importExport\Persistence\DataSourceInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class Queue
 * @package CPSIT\T3importExport\Domain\Model
 */
class Queue extends AbstractEntity
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\CPSIT\T3importExport\Domain\Model\QueueItem>
     */
    protected $queueItems;

    /**
     * @var string
     */
    protected $taskIdentifier;

    /**
     * @var string
     */
    protected $lockKey;

    /**
     * @var int
     */
    protected $size;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var DataSourceInterface
     */
    protected $dataSource;

    /**
     * description
     *
     * @var string
     */
    protected $description;

    public function initializeObject()
    {
        $this->queueItems = new ObjectStorage();
        $this->description = '';
        $this->size = 0;
        $this->offset = 0;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function getTaskIdentifier()
    {
        return $this->taskIdentifier;
    }

    /**
     * @param string $taskIdentifier
     */
    public function setTaskIdentifier($taskIdentifier)
    {
        $this->taskIdentifier = $taskIdentifier;
    }

    /**
     * @return string
     */
    public function getLockKey()
    {
        return $this->lockKey;
    }

    /**
     * @param string $lockKey
     */
    public function setLockKey($lockKey)
    {
        $this->lockKey = $lockKey;
    }

    /**
     * @return ObjectStorage
     */
    public function getQueueItems()
    {
        return $this->queueItems;
    }

    /**
     * @param ObjectStorage $queueItems
     */
    public function setQueueItems($queueItems)
    {
        $this->queueItems = $queueItems;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return DataSourceInterface
     */
    public function getDataSource()
    {
        return $this->dataSource;
    }

    /**
     * @param DataSourceInterface $dataSource
     */
    public function setDataSource(DataSourceInterface $dataSource)
    {
        $this->dataSource = $dataSource;
    }
}