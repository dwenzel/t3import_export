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

use CPSIT\ImportExportCore\Domain\Model\TransferTaskInterface;
use CPSIT\ImportExportCore\IdentifiableInterface;
use CPSIT\ImportExportCore\IdentifiableTrait;
use CPSIT\ImportExportCore\Persistence\DataSourceInterface;
use CPSIT\ImportExportCore\Persistence\DataTargetInterface;

/**
 * Class TransferTask
 * A transfer task describes a transfer from one source to a target
 * @todo refactor in to cpsit/import-export-core
 */
class TransferTask implements IdentifiableInterface, TransferTaskInterface
{
    use IdentifiableTrait;

    /**
     * Target class name
     *
     * @var string
     */
    protected string $targetClass = '';

    /**
     * Label
     *
     * @var string
     */
    protected string $label = '';
    /**
     * Description
     *
     * @var string
     */
    protected string $description = '';

    /**
     * @var DataSourceInterface|null
     */
    protected ?DataSourceInterface $source = null;

    /**
     * @var DataTargetInterface|null
     */
    protected ?DataTargetInterface $target = null;

    /**
     * Pre Processors
     *
     * @var array
     */
    protected array $preProcessors = [];

    /**
     * Post Processors
     *
     * @var array
     */
    protected array $postProcessors = [];

    /**
     * Converters
     *
     * @var array
     */
    protected array $converters = [];

    /**
     * Finishers
     *
     * @var array
     */
    protected array $finishers = [];

    /**
     * Initializers
     *
     * @var array
     */
    protected array $initializers = [];

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
     * @return string
     */
    public function getTargetClass(): string
    {
        return $this->targetClass;
    }

    /**
     * @param string $targetClass
     */
    public function setTargetClass(string $targetClass): void
    {
        $this->targetClass = $targetClass;
    }

    /**
     * Gets the source of import
     */
    public function getSource(): ?DataSourceInterface
    {
        return $this->source;
    }

    /**
     * Sets the source of import
     */
    public function setSource(DataSourceInterface $source): void
    {
        $this->source = $source;
    }

    /**
     * Gets the target of import
     *
     * @return ?DataTargetInterface
     */
    public function getTarget(): ?DataTargetInterface
    {
        return $this->target;
    }

    /**
     * Sets the target of import
     */
    public function setTarget(DataTargetInterface $target): void
    {
        $this->target = $target;
    }

    /**
     * Gets the pre-processors
     *
     * @return array
     */
    public function getPreProcessors(): array
    {
        return $this->preProcessors;
    }

    /**
     * Sets the pre-processors
     * @param array $preProcessors
     */
    public function setPreProcessors(array $preProcessors): void
    {
        $this->preProcessors = $preProcessors;
    }

    /**
     * Gets the post-processors
     *
     * @return array
     */
    public function getPostProcessors(): array
    {
        return $this->postProcessors;
    }

    /**
     * Sets the post-processors
     *
     * @param array $postProcessors
     */
    public function setPostProcessors(array $postProcessors): void
    {
        $this->postProcessors = $postProcessors;
    }

    /**
     * Gets the converters
     *
     * @return array
     */
    public function getConverters(): array
    {
        return $this->converters;
    }

    /**
     * Sets the converters
     *
     * @param array $converters
     */
    public function setConverters(array $converters): void
    {
        $this->converters = $converters;
    }

    /**
     * Gets the finishers
     *
     * @return array
     */
    public function getFinishers(): array
    {
        return $this->finishers;
    }

    /**
     * sets the finishers
     *
     * @param array $finishers
     */
    public function setFinishers(array $finishers): void
    {
        $this->finishers = $finishers;
    }

    /**
     * Gets the initializers
     *
     * @return array
     */
    public function getInitializers(): array
    {
        return $this->initializers;
    }

    /**
     * Sets the initializers
     *
     * @param array $initializers
     */
    public function setInitializers(array $initializers): void
    {
        $this->initializers = $initializers;
    }

    /**
     * Gets the label
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
