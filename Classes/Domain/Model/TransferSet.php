<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Domain\Model;

/***************************************************************
 *  Copyright notice
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use CPSIT\ImportExportCore\IdentifiableInterface;
use CPSIT\ImportExportCore\IdentifiableTrait;
use CPSIT\ImportExportCore\Domain\Model\TransferSetInterface;

/**
 * Class TransferSet
 * @todo refactor in to cpsit/import-export-core
 * A set of transfer tasks
 */
class TransferSet implements IdentifiableInterface, TransferSetInterface
{
    use IdentifiableTrait;

    /**
     * Description
     *
     * @var string
     */
    protected string $description;

    /**
     * Label
     *
     * @var string
     */
    protected string $label;

    /**
     * Tasks to perform
     *
     * @var array
     */
    protected array $tasks;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return array
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * @param array $tasks
     */
    public function setTasks(array $tasks): void
    {
        $this->tasks = $tasks;
    }

    /**
     * Gets the label
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Sets the label
     *
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }
}
