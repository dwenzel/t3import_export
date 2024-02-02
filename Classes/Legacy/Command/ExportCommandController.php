<?php
namespace CPSIT\T3importExport\Legacy\Command;

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

/**
 * Class ExportCommandController
 * Provides export commands for cli and scheduler tasks
 * @deprecated from TYPO3 10 on use the new symfony commands instead
 */
class ExportCommandController extends TransferCommandController
{
    /**
     * Key under which configuration are found in
     * Framework configuration.
     * This should match the key for the ExportController
     */
    final public const SETTINGS_KEY = ExportController::SETTINGS_KEY;
}
