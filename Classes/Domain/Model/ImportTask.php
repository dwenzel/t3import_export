<?php
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
use CPSIT\T3importExport\IdentifiableInterface;
use CPSIT\T3importExport\IdentifiableTrait;
use CPSIT\T3importExport\Persistence\DataSourceInterface;
use CPSIT\T3importExport\Persistence\DataTargetInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class ImportTask
 * An import task describes an import from one source to one class
 *
 * @package CPSIT\T3importExport\Domain\Model
 */
class ImportTask
	extends AbstractEntity
	implements IdentifiableInterface {
	use IdentifiableTrait;

	/**
	 * Target class name
	 *
	 * @var string
	 */
	protected $targetClass;

	/**
	 * @var string
	 */
	protected $description;

	/**
	 * @var DataSourceInterface
	 */
	protected $source;

	/**
	 * @var DataTargetInterface
	 */
	protected $target;

	/**
	 * Pre Processors
	 *
	 * @var array
	 */
	protected $preProcessors = [];

	/**
	 * Post Processors
	 *
	 * @var array
	 */
	protected $postProcessors = [];

	/**
	 * Converters
	 *
	 * @var array
	 */
	protected $converters = [];

	/**
	 * Finishers
	 *
	 * @var array
	 */
	protected $finishers = [];

	/**
	 * Initializers
	 *
	 * @var array
	 */
	protected $initializers = [];

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function getTargetClass() {
		return $this->targetClass;
	}

	/**
	 * @param string $targetClass
	 */
	public function setTargetClass($targetClass) {
		$this->targetClass = $targetClass;
	}

	/**
	 * Gets the source of import
	 *
	 * @return DataSourceInterface
	 */
	public function getSource() {
		return $this->source;
	}

	/**
	 * Sets the source of import
	 *
	 * @param DataSourceInterface $source
	 */
	public function setSource(DataSourceInterface $source) {
		$this->source = $source;
	}

	/**
	 * Gets the target of import
	 *
	 * @return DataTargetInterface
	 */
	public function getTarget() {
		return $this->target;
	}

	/**
	 * Sets the target of import
	 *
	 * @param DataTargetInterface $target
	 */
	public function setTarget(DataTargetInterface $target) {
		$this->target = $target;
	}

	/**
	 * @return array
	 */
	public function getPreProcessors() {
		return $this->preProcessors;
	}

	/**
	 * @param array $preProcessors
	 */
	public function setPreProcessors($preProcessors) {
		$this->preProcessors = $preProcessors;
	}

	/**
	 * @return array
	 */
	public function getPostProcessors() {
		return $this->postProcessors;
	}

	/**
	 * @param array $postProcessors
	 */
	public function setPostProcessors($postProcessors) {
		$this->postProcessors = $postProcessors;
	}

	/**
	 * @return array
	 */
	public function getConverters() {
		return $this->converters;
	}

	/**
	 * @param array $converters
	 */
	public function setConverters($converters) {
		$this->converters = $converters;
	}

	/**
	 * @return array
	 */
	public function getFinishers() {
		return $this->finishers;
	}

	/**
	 * @param array $finishers
	 */
	public function setFinishers($finishers) {
		$this->finishers = $finishers;
	}

	/**
	 * @return array
	 */
	public function getInitializers() {
		return $this->initializers;
	}

	/**
	 * @param array $initializers
	 */
	public function setInitializers($initializers) {
		$this->initializers = $initializers;
	}
}
