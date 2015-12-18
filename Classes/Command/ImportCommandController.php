<?php
namespace CPSIT\T3import\Command;

use CPSIT\T3import\Service\ImportProcessor;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

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
class ImportCommandController extends CommandController {

	/**
	 * @var ImportProcessor
	 */
	protected $importProcessor;

	/**
	 * Injects the event import processor
	 *
	 * @param ImportProcessor $importProcessor
	 */
	public function injectImportProcessor(ImportProcessor $importProcessor) {
		$this->importProcessor = $importProcessor;
	}

	/**
	 * Imports events
	 *
	 * @param int $queueLength Queue length: How many events should be imported at once
	 * @param bool $dryRun Dry run: If set no event will be saved
	 * @param string |null $email : Notification email: If set, a summary of the import will be send to it.
	 */
	public function importCommand($queueLength = 100, $dryRun = FALSE, $email = NULL) {
		$this->importProcessor->buildQueue($queueLength);
		$this->importProcessor->process();
	}
}
