<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Command;

use CPSIT\ImportExportCore\Configuration\YamlConfigurationLoader;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Dirk Wenzel <wenzel@cps-it.de>
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
 * Class TransferCommandController
 */
trait TransferCommandTrait
{
    protected array $settings;

    public function withSettings(array $settings): self
    {
        $this->settings = $settings;
        return $this;
    }

    /**
     * initialize object
     */
    public function initializeObject(): void
    {
        $fullConfiguration = $this->configurationHandler->getFullConfiguration(
        );

        // ['import' => ['tasks' => ..., 'sets' => ...], 'export' => [...]]
        if (isset($fullConfiguration[static::SETTINGS_KEY])) {
            $this->settings = $fullConfiguration[static::SETTINGS_KEY];
        }

    }

    /**
     * Load YAML configuration from file
     *
     * @param string $yamlFile Path to YAML configuration file
     * @return bool false if the file could not be loaded
     */
    protected function loadYamlConfiguration(string $yamlFile): bool
    {
        $yamlLoader = GeneralUtility::makeInstance(YamlConfigurationLoader::class);

        try {
            // Add to the shared handler, so that TransferSetFactory finds the tasks of the file
            $this->configurationHandler->addConfiguration($yamlLoader, $yamlFile);
            $config = $this->configurationHandler->getFullConfiguration();

            if (isset($config[static::SETTINGS_KEY])) {
                // YAML configuration overrides existing settings
                $settings = $this->settings ?? [];
                ArrayUtility::mergeRecursiveWithOverrule($settings, $config[static::SETTINGS_KEY]);
                $this->settings = $settings;
            }
        } catch (\Exception $e) {
            $this->io->error('Error loading YAML configuration: ' . $e->getMessage());
            return false;
        }

        return true;
    }
}
