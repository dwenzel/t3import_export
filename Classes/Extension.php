<?php

declare(strict_types=1);

namespace CPSIT\T3importExport;

use CPSIT\ImportExportCore\Configuration\YamlConfigurationProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * Extension bootstrap class
 */
class Extension
{
    /**
     * Boot the extension
     */
    public function boot(): void
    {
        // Define default YAML configuration directories
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories'])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories'] = [
                'EXT:t3import_export/Configuration/ImportExport',
            ];
        }

        // Load YAML configurations
        $this->loadYamlConfigurations();
    }

    /**
     * Load YAML configurations from configured directories
     */
    protected function loadYamlConfigurations(): void
    {
        if (empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories'])) {
            return;
        }

        /** @var YamlConfigurationProvider $yamlConfigProvider */
        $yamlConfigProvider = GeneralUtility::makeInstance(YamlConfigurationProvider::class);

        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories'] as $directory) {
            $resolvedPath = GeneralUtility::getFileAbsFileName($directory);
            if (is_dir($resolvedPath)) {
                $yamlConfigProvider->loadFromDirectory($resolvedPath);
            }
        }
    }
}
