<?php

declare(strict_types=1);

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

namespace CPSIT\T3importExport\Command;

use CPSIT\T3importExport\Command\Argument\TaskArgument;
use CPSIT\T3importExport\Controller\ImportController;
use DWenzel\T3extensionTools\Command\ArgumentAwareInterface;
use DWenzel\T3extensionTools\Traits\Command\ArgumentAwareTrait;
use DWenzel\T3extensionTools\Traits\Command\ConfigureTrait;
use DWenzel\T3extensionTools\Traits\Command\InitializeTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

/**
 * Provides the import task command for cli and scheduler tasks
 */
#[AsCommand(
    name: ImportTaskCommand::DEFAULT_NAME,
    description: ImportTaskCommand::MESSAGE_DESCRIPTION_COMMAND,
    aliases: ImportTaskCommand::COMMAND_ALIASES
)]
class ImportTaskCommand extends Command implements ArgumentAwareInterface
{
    use ConfigureTrait;
    use InitializeTrait;
    use ArgumentAwareTrait;
    use TaskCommandTrait;

    /**
     * Key under which configuration are found in
     * Framework configuration.
     * This should match the key for the ImportController
     */
    final public const string SETTINGS_KEY = ImportController::SETTINGS_KEY;

    final public const string DEFAULT_NAME = 't3import-export:import-task';
    final public const array COMMAND_ALIASES = ['import:task'];
    final public const string MESSAGE_DESCRIPTION_COMMAND = 'Performs pre-defined import task.';
    final public const string MESSAGE_HELP_COMMAND = '@todo: help command';
    final public const string MESSAGE_SUCCESS = 'Import task successfully processed';
    final public const string MESSAGE_STARTING = 'Starting import task';
    final public const string WARNING_MISSING_PARAMETER = 'Parameter "%s" must not be omitted';
    final public const string WARNING_MISSING_CONFIGURATION = 'No configuration found for task with identifier "%s".';
    final public const array OPTIONS = [];
    final public const array ARGUMENTS = [
        TaskArgument::class,
    ];

    /**
     * @var array|string[]
     */
    protected static array $optionsToConfigure = self::OPTIONS;
    protected static array $argumentsToConfigure = self::ARGUMENTS;
}
