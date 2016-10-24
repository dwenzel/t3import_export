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
use CPSIT\T3importExport\Domain\Model\TransferSet;
use CPSIT\T3importExport\Factory\AbstractFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TransferSetFactory
 * builds import sets from settings
 *
 * @package CPSIT\T3importExport\Domain\Repository
 */
class TransferSetFactory extends AbstractFactory {

	/**
	 * @var \CPSIT\T3importExport\Domain\Factory\TransferTaskFactory
	 */
	protected $transferTaskFactory;

	/**
     * Injects the transfer task factory
     *
	 * @param TransferTaskFactory $transferTaskFactory
	 */
	public function injectTransferTaskFactory(TransferTaskFactory $transferTaskFactory) {
		$this->transferTaskFactory = $transferTaskFactory;
	}

	/**
	 * Builds a set of tasks
	 *
	 * @param array $settings
	 * @param string $identifier
	 * @return TransferSet
	 * @throws \CPSIT\T3importExport\InvalidConfigurationException
	 */
	public function get(array $settings, $identifier = null) {
		/** @var TransferSet $transferSet */
		$transferSet = $this->objectManager->get(
			TransferSet::class
		);

		$transferSet->setIdentifier($identifier);

		if (isset($settings['tasks'])
			AND is_string($settings['tasks'])
		) {
			$taskIdentifiers = GeneralUtility::trimExplode(',', $settings['tasks'], true);
			$tasks = [];
			foreach ($taskIdentifiers as $taskIdentifier) {
				if (isset($this->settings['importProcessor']['tasks'][$taskIdentifier])) {
					$task = $this->transferTaskFactory->get(
						$this->settings['importProcessor']['tasks'][$taskIdentifier], $taskIdentifier
					);
					$tasks[$taskIdentifier] = $task;
				}
			}
			$transferSet->setTasks($tasks);
		}

		if (isset($settings['description'])
			AND is_string($settings['description'])
		) {
			$transferSet->setDescription($settings['description']);
		}

		if (isset($settings['label']))
		{
			$transferSet->setLabel($settings['label']);
		}

		return $transferSet;
	}

}
