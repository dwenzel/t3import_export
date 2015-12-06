<?php
namespace CPSIT\T3import\Domain\Factory;

use CPSIT\T3import\Domain\Model\ImportTask;

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
	 * Builds a task
	 *
	 * @param string $identifier
	 * @param array $settings
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

		return $task;
	}

}