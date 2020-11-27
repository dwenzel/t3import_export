<?php
declare(strict_types=1);

namespace CPSIT\T3importExport\Configuration;

/***************************************************************
 * Copyright notice
 *
 * Copyright (C) 2020 Dirk Wenzel
 * All rights reserved
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

use DWenzel\T3extensionTools\Configuration\ExtensionConfiguration as BaseConfiguration;
use CPSIT\T3importExport\Configuration\SettingsInterface as SI;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extension
 */
final class ExtensionConfiguration extends BaseConfiguration
{
    public const KEY = SI::EXTENSION_KEY;
    public const NAME = SI::EXTENSION_NAME;
    public const VENDOR_NAME = SI::VENDOR_NAME;
    public const TABLES_ALLOWED_ON_STANDARD_PAGES = [
        'tx_t3importexport_domain_model_exporttarget'
    ];
    public const MODULES_TO_REGISTER = [
    ];

    protected const PLUGINS_TO_REGISTER = [
    ];


    public static function registerIcons()
    {
        /** @var IconRegistry $iconRegistry */
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);

        foreach (SI::ICONS_TO_REGISTER as $identifier => $config) {
            $iconRegistry->registerIcon(
                $identifier,
                $config[SI::ICON_PROVIDER_CLASS_KEY],
                $config[SI::ICON_OPTIONS_KEY]
            );
        }
        parent::registerIcons();
    }
}
