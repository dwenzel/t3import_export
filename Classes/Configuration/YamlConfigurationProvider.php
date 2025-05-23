<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Configuration;

use CPSIT\ImportExportCore\Configuration\ConfigurationManager;
use CPSIT\ImportExportCore\Configuration\YamlConfigurationLoader;
use TYPO3\CMS\Core\SingletonInterface;

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
 * Provider for YAML configuration
 */
class YamlConfigurationProvider implements SingletonInterface
{
    /**
     * @var ConfigurationManager
     */
    private ConfigurationManager $configurationManager;
    
    /**
     * @var YamlConfigurationLoader
     */
    private YamlConfigurationLoader $yamlLoader;
    
    /**
     * YamlConfigurationProvider constructor.
     *
     * @param ConfigurationManager $configurationManager
     * @param YamlConfigurationLoader $yamlLoader
     */
    public function __construct(
        ConfigurationManager $configurationManager,
        YamlConfigurationLoader $yamlLoader
    ) {
        $this->configurationManager = $configurationManager;
        $this->yamlLoader = $yamlLoader;
    }
    
    /**
     * Load YAML configuration files from a directory
     *
     * @param string $directory Directory containing YAML files
     * @param string $extension File extension (default: yaml)
     * @return array Loaded configuration
     */
    public function loadFromDirectory(string $directory, string $extension = 'yaml'): array
    {
        if (!is_dir($directory)) {
            return $this->configurationManager->getFullConfiguration();
        }
        
        $files = scandir($directory);
        if ($files === false) {
            return $this->configurationManager->getFullConfiguration();
        }
        
        $yamlFiles = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $filePath = $directory . '/' . $file;
            if (is_file($filePath) && pathinfo($file, PATHINFO_EXTENSION) === $extension) {
                $yamlFiles[] = $filePath;
            }
        }
        
        // Sort files to ensure consistent order
        sort($yamlFiles);
        
        foreach ($yamlFiles as $file) {
            $this->configurationManager->addConfiguration($this->yamlLoader, $file);
        }
        
        return $this->configurationManager->getFullConfiguration();
    }
}