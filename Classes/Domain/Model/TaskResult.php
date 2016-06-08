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


class TaskResult implements \Iterator
{
    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var array
     */
    private $list = [];

    /**
     * @var int
     */
    private $size = 0;

    public function __construct()
    {
        $this->list = [];
        $this->position = 0;
        $this->size = 0;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->list[$this->position];
    }

    /**
     * @return mixed
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * @return mixed
     */
    public function valid()
    {
        return isset($this->list[$this->position]);
    }

    public function count()
    {
        return $this->size;
    }

    /**
     * @return mixed
     */
    public function rewind()
    {
        $this->position = 0;
    }

    public function setElements(array $elements)
    {
        $this->list = $elements;
        $this->size = count($elements);
        $this->rewind();
    }

    public function add($newElement)
    {
        $this->list[] = $newElement;
        ++$this->size;
    }

    public function removeElement($element)
    {
        $key = array_search($element, $this->list);
        if ($key !== false) {
            return $this->removeIndex($key);
        }
        return false;
    }

    public function removeIndex($index)
    {
        if ($this->size > $index) {
            unset($this->list[$index]);
            --$this->size;
            // if remove element after current
            if ($index >= $this->position && $this->position > 0) {
                --$this->position;
            }
        }
    }

    public function toArray()
    {
        return $this->list;
    }
}