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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class QueueItem
 * @package CPSIT\T3importExport\Domain\Model
 */
class QueueItem extends AbstractEntity
{
    /**
     * @var int
     */
    protected $dataSourceIndex;

    /**
     * @var \CPSIT\T3importExport\Domain\Model\Queue
     */
    protected $queue;

    /**
     * description
     *
     * @var string
     */
    protected $description;

    public function initializeObject()
    {
        $this->description = '';
        $this->dataSourceIndex = 0;
    }

    /**
     * @return int
     */
    public function getDataSourceIndex()
    {
        return $this->dataSourceIndex;
    }

    /**
     * @param int $dataSourceIndex
     */
    public function setDataSourceIndex($dataSourceIndex)
    {
        $this->dataSourceIndex = (int)$dataSourceIndex;
    }

    /**
     * @return Queue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * @param Queue $queue
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
    }
}