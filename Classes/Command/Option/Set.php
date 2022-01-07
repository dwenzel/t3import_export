<?php

namespace CPSIT\T3importExport\Command\Option;

use DWenzel\T3extensionTools\Command\Option\CommandOptionInterface;
use DWenzel\T3extensionTools\Traits\Command\Option\CommandOptionTrait;
use Symfony\Component\Console\Input\InputOption;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2022 Dirk Wenzel <wenzel@cps-it.de>
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
class Set implements CommandOptionInterface
{
    use CommandOptionTrait;

    public const NAME = 'set';
    public const HELP = 'identifier of set to process';
    public const MODE = InputOption::VALUE_REQUIRED;
    public const DESCRIPTION = 'set identifier';
    public const SHORTCUT = 's';
    public const DEFAULT = null;

}
