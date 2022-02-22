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

use CPSIT\T3importExport\Command\Argument\SetArgument;
use CPSIT\T3importExport\Controller\ExportController;
use DWenzel\T3extensionTools\Command\ArgumentAwareInterface;
use DWenzel\T3extensionTools\Traits\Command\ArgumentAwareTrait;
use DWenzel\T3extensionTools\Traits\Command\ConfigureTrait;
use DWenzel\T3extensionTools\Traits\Command\InitializeTrait;
use Symfony\Component\Console\Command\Command;
/**
 * Class ExportCommandController
 * Provides export commands for cli and scheduler tasks
 */
class ExportSetCommand extends Command implements ArgumentAwareInterface
{
    use ArgumentAwareTrait,
        ConfigureTrait,
        InitializeTrait,
        SetCommandTrait;
    /**
     * Key under which configuration are found in
     * Framework configuration.
     * This should match the key for the ExportController
     */
    const SETTINGS_KEY = ExportController::SETTINGS_KEY;

    public const DEFAULT_NAME = 't3import-export:import-set';
    public const MESSAGE_DESCRIPTION_COMMAND = 'Performs pre-defined export set.';
    public const MESSAGE_HELP_COMMAND = '@todo: help command';
    public const MESSAGE_SUCCESS = 'Export set successfully processed';
    public const MESSAGE_STARTING = 'Starting export set';
    public const WARNING_MISSING_PARAMETER = 'Parameter %s must not be omitted';
    public const OPTIONS = [
    ];
    public const ARGUMENTS = [
        SetArgument::class
    ];

    /**
     * @var array|string[]
     */
    static protected array $optionsToConfigure = self::OPTIONS;
    static protected array $argumentsToConfigure = self::ARGUMENTS;

}

