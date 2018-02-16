<?php
namespace CPSIT\T3importExport\Command;

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
use CPSIT\T3importExport\Controller\ExportController;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Class ExportCommandController
 * Provides export commands for cli and scheduler tasks
 */
class ExportCommandController extends TransferCommandController
{
    /**
     * Key under which configuration are found in
     * Framework configuration.
     * This should match the key for the ExportController
     */
    const SETTINGS_KEY = ExportController::SETTINGS_KEY;

    /**
     * initialize object
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function initializeObject() {
        $extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );

        if (isset($extbaseFrameworkConfiguration['settings'][static::SETTINGS_KEY])) {
            $this->settings = $extbaseFrameworkConfiguration['settings'][static::SETTINGS_KEY];
        }
    }
}
