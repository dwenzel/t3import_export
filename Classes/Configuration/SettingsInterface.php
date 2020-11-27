<?php

namespace CPSIT\T3importExport\Configuration;

use TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider;

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
interface SettingsInterface
{
    /**
     * Key for icon registration
     */
    const ICON_NAME_KEY = 'name';
    const ICON_PROVIDER_CLASS_KEY = 'iconProviderClass';
    const ICON_OPTIONS_KEY = 'options';


    const EXTENSION_KEY = 't3import_export';
    const EXTENSION_NAME = 'T3ImportExport';
    const VENDOR_NAME = 'CPSIT';

    const RESOURCES_PATH = 'typo3conf/ext/t3import_export/Resources/';

    const ICON_IDENTIFIER_EXPORT_TARGET = 't3import-export-export-target';

    /**
     * Icons to register via API
     */
    const ICONS_TO_REGISTER = [
        self::ICON_IDENTIFIER_EXPORT_TARGET => [
            self::ICON_PROVIDER_CLASS_KEY => FontawesomeIconProvider::class,
            self::ICON_OPTIONS_KEY => [
                self::ICON_NAME_KEY => 'bullseye'
            ]
        ]
    ];
}
