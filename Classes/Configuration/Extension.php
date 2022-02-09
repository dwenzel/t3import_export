<?php

namespace CPSIT\T3importExport\Configuration;

use DWenzel\T3extensionTools\Configuration\ExtensionConfiguration;
use CPSIT\T3importExport\Configuration\Module\ImportModuleRegistration;
use CPSIT\T3importExport\Configuration\Module\ExportModuleRegistration;

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
class Extension extends ExtensionConfiguration
{
    public const KEY = 't3import_export';
    public const NAME = 'T3importExport';
    public const VENDOR_NAME = 'CPSIT';

    public const MODULES_TO_REGISTER = [
        ImportModuleRegistration::class,
        ExportModuleRegistration::class
    ];

}
