<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Command;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2026 Dirk Wenzel <wenzel@cps-it.de>
 *  All rights reserved
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use CPSIT\T3importExport\Command\Argument\TaskArgument;
use CPSIT\T3importExport\Command\Option\YamlConfigFileOption;
use CPSIT\T3importExport\Controller\ExportController;
use DWenzel\T3extensionTools\Command\ArgumentAwareInterface;
use DWenzel\T3extensionTools\Command\OptionAwareInterface;
use DWenzel\T3extensionTools\Traits\Command\ArgumentAwareTrait;
use DWenzel\T3extensionTools\Traits\Command\ConfigureTrait;
use DWenzel\T3extensionTools\Traits\Command\InitializeTrait;
use DWenzel\T3extensionTools\Traits\Command\OptionAwareTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

/**
 * Provides the export task command for cli and scheduler tasks
 */
#[AsCommand(
    name: ExportTaskCommand::DEFAULT_NAME,
    description: ExportTaskCommand::MESSAGE_DESCRIPTION_COMMAND,
    aliases: ExportTaskCommand::COMMAND_ALIASES
)]
class ExportTaskCommand extends Command implements ArgumentAwareInterface, OptionAwareInterface
{
    use ArgumentAwareTrait;
    use ConfigureTrait;
    use InitializeTrait;
    use OptionAwareTrait;
    use TaskCommandTrait;

    /**
     * Key under which configuration are found in
     * Framework configuration.
     * This should match the key for the ExportController
     */
    final public const string SETTINGS_KEY = ExportController::SETTINGS_KEY;

    final public const string DEFAULT_NAME = 't3import-export:export-task';
    final public const array COMMAND_ALIASES = ['export:task'];
    final public const string MESSAGE_DESCRIPTION_COMMAND = 'Performs pre-defined export task.';
    final public const string MESSAGE_HELP_COMMAND = '@todo: help command';
    final public const string MESSAGE_SUCCESS = 'Export task successfully processed';
    final public const string MESSAGE_STARTING = 'Starting export task';
    final public const string WARNING_MISSING_PARAMETER = 'Parameter "%s" must not be omitted';
    final public const string WARNING_MISSING_CONFIGURATION = 'No configuration found for task with identifier "%s".';
    final public const array OPTIONS = [
        YamlConfigFileOption::class,
    ];
    final public const array ARGUMENTS = [
        TaskArgument::class,
    ];

    /**
     * @var array|string[]
     */
    protected static array $optionsToConfigure = self::OPTIONS;
    protected static array $argumentsToConfigure = self::ARGUMENTS;
}
