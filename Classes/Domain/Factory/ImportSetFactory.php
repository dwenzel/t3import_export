<?php
namespace CPSIT\T3importExport\Domain\Factory;

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
use CPSIT\T3importExport\Domain\Model\ImportSet;
use CPSIT\T3importExport\Factory\AbstractFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ImportSetFactory
 * builds import sets from settings
 *
 * @package CPSIT\T3importExport\Domain\Repository
 */
class ImportSetFactory extends AbstractFactory {

	/**
	 * @var \CPSIT\T3importExport\Domain\Factory\ImportTaskFactory
	 */
	protected $importTaskFactory;

	/**
	 * @param ImportTaskFactory $importTaskFactory
	 */
	public function injectImportTaskFactory(ImportTaskFactory $importTaskFactory) {
		$this->importTaskFactory = $importTaskFactory;
	}

	/**
	 * Builds a set of tasks
	 *
	 * @param array $settings
	 * @param string $identifier
	 * @return ImportSet
	 * @throws \CPSIT\T3importExport\InvalidConfigurationException
	 */
	public function get(array $settings, $identifier = null) {
		/** @var ImportSet $importSet */
		$importSet = $this->objectManager->get(
			ImportSet::class
		);

		$importSet->setIdentifier($identifier);

		if (isset($settings['tasks'])
			AND is_string($settings['tasks'])
		) {
			$taskIdentifiers = GeneralUtility::trimExplode(',', $settings['tasks'], true);
			$tasks = [];
			foreach ($taskIdentifiers as $taskIdentifier) {
				if (isset($this->settings['importProcessor']['tasks'][$taskIdentifier])) {
					$task = $this->importTaskFactory->get(
						$this->settings['importProcessor']['tasks'][$taskIdentifier], $taskIdentifier
					);
					$tasks[$taskIdentifier] = $task;
				}
			}
			$importSet->setTasks($tasks);
		}

		if (isset($settings['description'])
			AND is_string($settings['description'])
		) {
			$importSet->setDescription($settings['description']);
		}

		return $importSet;
	}

}
