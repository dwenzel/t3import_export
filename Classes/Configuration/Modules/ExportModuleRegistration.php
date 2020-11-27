<?php

namespace CPSIT\T3importExport\Configuration\Modules;

use DWenzel\T3extensionTools\Configuration\ModuleRegistrationInterface;
use DWenzel\T3extensionTools\Configuration\ModuleRegistrationTrait;
use CPSIT\T3importExport\Configuration\ExtensionConfiguration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2020 Dirk Wenzel <wenzel@cps-it.de>
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
class ExportModuleRegistration implements ModuleRegistrationInterface
{
    use ModuleRegistrationTrait;

    public const ROUTE = 'system_T3importExportImport';

    static protected $subModuleName = 'export';
    static protected $mainModuleName = 'system';
    static protected $vendorExtensionName = ExtensionConfiguration::VENDOR_NAME . '.' . ExtensionConfiguration::NAME;
    static protected $controllerActions = [
        'Import' => 'index,importTask,importSet',
    ];

    static protected $position = 'bottom';
    static protected $moduleConfiguration = [
        'access' => 'group,user',
        'icon' => 'EXT:t3import_export/Resources/Public/Icons/ext_icon.svg',
        'labels' => 'LLL:EXT:t3import_export/Resources/Private/Language/locallang_import.xlf',
    ];

}
