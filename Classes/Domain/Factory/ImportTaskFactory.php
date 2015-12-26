<?php
namespace CPSIT\T3import\Domain\Factory;

use CPSIT\T3import\Domain\Model\ImportTask;
use CPSIT\T3import\Factory\AbstractFactory;
use CPSIT\T3import\Persistence\DataTargetRepository;
use CPSIT\T3import\Persistence\Factory\DataSourceFactory;
use CPSIT\T3import\Persistence\Factory\DataTargetFactory;
use CPSIT\T3import\Persistence\MissingClassException;
use CPSIT\T3import\Service\InvalidConfigurationException;

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

/**
 * Class ImportTaskFactory
 * builds import tasks from settings
 *
 * @package CPSIT\T3import\Domain\Factory
 */
class ImportTaskFactory extends AbstractFactory {

	/**
	 * @var DataTargetFactory
	 */
	protected $dataTargetFactory;

	/**
	 * @var DataSourceFactory
	 */
	protected $dataSourceFactory;

	/**
	 * injects the data target factory
	 *
	 * @param DataTargetFactory $factory
	 */
	public function injectDataTargetFactory(DataTargetFactory $factory) {
		$this->dataTargetFactory = $factory;
	}

	/**
	 * injects the data source factory
	 *
	 * @param DataSourceFactory $factory
	 */
	public function injectDataSourceFactory(DataSourceFactory $factory) {
		$this->dataSourceFactory = $factory;
	}

	/**
	 * Builds a task
	 *
	 * @param string $identifier
	 * @param array $settings
	 * @throws InvalidConfigurationException
	 * @return ImportTask
	 */
	public function get($identifier, array $settings) {
		/** @var ImportTask $task */
		$task = $this->objectManager->get(
			ImportTask::class
		);
		$task->setIdentifier($identifier);

		if (isset($settings['class'])
			AND is_string($settings['class'])
		) {
			$task->setTargetClass($settings['class']);
		}

		if (isset($settings['description'])
			AND is_string($settings['description'])
		) {
			$task->setDescription($settings['description']);
		}

		if (!isset($settings['target'])
			OR !is_array(($settings['target']))
		) {
			throw new InvalidConfigurationException(
				'Invalid configuration for import task ' . $identifier .
				' Target is missing or is not an array.',
				1451052262
			);
		}

		$task->setTarget(
			$this->dataTargetFactory->get($identifier, $settings['target'])
		);

		return $task;
	}
}