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

use CPSIT\T3importExport\Messaging\MessageContainer;
use CPSIT\T3importExport\Messaging\MessageContainerTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TaskResult implements \Iterator
{
    use MessageContainerTrait;


    /**
     * @var int
     */
    protected $position = 0;

    /**
     * @var array
     */
    protected $list = [];

    /**
     * @var int
     */
    protected $size = 0;

    /**
     * @var null|mixed
     */
    protected $info;

    /**
     * TaskResult constructor.
     * @param MessageContainer|null $messageContainer
     */
    public function __construct(MessageContainer $messageContainer = null)
    {
        $this->list = [];
        $this->position = 0;
        $this->size = 0;
        $this->info = null;
        $this->messageContainer = $messageContainer ?? GeneralUtility::makeInstance(MessageContainer::class);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->list[$this->position];
    }

    /**
     * @return void
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return isset($this->list[$this->position]);
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->size;
    }

    /**
     * @return void
     * @return void
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @param array $elements
     * @return void
     */
    public function setElements(array $elements)
    {
        $this->list = $elements;
        $this->size = count($elements);
        $this->rewind();
    }

    /**
     * @param $newElement
     * @return void
     */
    public function add($newElement)
    {
        $this->list[] = $newElement;
        ++$this->size;
    }

    /**
     * @param $element
     * @return bool
     */
    public function removeElement($element)
    {
        $key = array_search($element, $this->list);
        if ($key !== false) {
            return $this->removeIndex($key);
        }
        return false;
    }

    /**
     * @param $index
     * @return bool
     */
    public function removeIndex($index)
    {
        if ($this->size > $index) {
            unset($this->list[$index]);
            --$this->size;
            // if remove element after current
            if ($index >= $this->position && $this->position > 0) {
                --$this->position;
            }
            // reindex list
            $this->list = array_values($this->list);
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->list;
    }

    /**
     * @param $mixed
     * @return void
     */
    public function setInfo($mixed)
    {
        $this->info = $mixed;
    }

    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Adds all messages.
     * Existing messages are kept.
     *
     * @param array $messages
     */
    public function addMessages(array $messages) {
        $this->messageContainer->addMessages($messages);
    }
}
