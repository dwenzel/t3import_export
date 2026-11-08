<?php
/*
 * This file is part of the iki-bundle project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

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
    protected int $position = 0;

    /**
     * @var array
     */
    protected array $list = [];

    /**
     * @var int
     */
    protected int $size = 0;

    /**
     * @var null|mixed
     */
    protected $info = null;

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
    public function current(): mixed
    {
        return $this->list[$this->position];
    }

    /**
     * @return void
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->list[$this->position]);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->size;
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @param array $elements
     * @return void
     */
    public function setElements(array $elements): void
    {
        $this->list = $elements;
        $this->size = count($elements);
        $this->rewind();
    }

    /**
     * @param $newElement
     * @return void
     */
    public function add($newElement): void
    {
        $this->list[] = $newElement;
        ++$this->size;
    }

    /**
     * @param $element
     * @return bool
     */
    public function removeElement($element): bool
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
    public function removeIndex($index): bool
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
    public function toArray(): array
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
