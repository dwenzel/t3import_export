<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Command\Option;

use DWenzel\T3extensionTools\Command\Option\InputOptionInterface;
use DWenzel\T3extensionTools\Traits\Command\Option\InputOptionTrait;
use Symfony\Component\Console\Input\InputOption;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2025 Dirk Wenzel <wenzel@cps-it.de>
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

/**
 * Option for YAML configuration file
 */
class YamlConfigFileOption implements InputOptionInterface
{
    use InputOptionTrait;

    final public const string NAME = 'yaml-config';
    final public const string HELP = 'Path to YAML configuration file';
    final public const int MODE = InputOption::VALUE_REQUIRED;
    final public const string DESCRIPTION = 'YAML configuration file';
    final public const string SHORTCUT = 'y';
    final public const null DEFAULT = null;
}
