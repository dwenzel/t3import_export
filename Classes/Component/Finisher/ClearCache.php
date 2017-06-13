<?php
namespace CPSIT\T3importExport\Component\Finisher;

use CPSIT\T3importExport\Component\Finisher\AbstractFinisher;
use CPSIT\T3importExport\Component\Finisher\FinisherInterface;
use CPSIT\T3importExport\ConfigurableInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\CacheService;
use TYPO3\CMS\Extbase\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/***************************************************************
 *  Copyright notice
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class ClearCache extends AbstractFinisher implements FinisherInterface, ConfigurableInterface
{
    /**
     * @var CacheService
     */
    protected $cacheService;

    /**
     * Injects the cache service
     *
     * @param CacheService $cacheService
     */
    public function injectCacheService(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Tells whether the given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        return parent::isConfigurationValid($configuration);
        // TODO: validate global/classes config (array, class names), pages (string)
    }

    /**
     * Clears the page class depending on result and configuration
     *
     * Configuration examples (TypoScript):
     * 1. clear all if any result
     * config {
     *  all = 1
     * }
     * 2. clear selected pages (if any result)
     * config {
     *  pages = '1,3,5'
     * }
     * 3. clear all if any result for given class name
     * ($result[0] must contain an object of the given class)
     * config {
     *  classes {
     *   NameSpaced\ClassName\OfResult {
     *    all = 1
     *   }
     *  }
     * }
     * 4. clear selected pages if any result for given class name
     * ($result[0] must contain an object of the given class)
     * config {
     *  classes {
     *   NameSpaced\ClassName\OfResult {
     *    pages = 1,3,5
     *   }
     *  }
     * }

     * @param array $configuration
     * @param array $records
     * @param array $result
     * @return bool
     */
    public function process($configuration, &$records, &$result)
    {
        if (!(bool)$result) {
            // nothing imported - do not clear any cache
            return true;
        }

        if ($this->shouldClearAll($configuration)) {
            $this->cacheService->clearPageCache();
            return true;
        }

        $pagesToClear = [];
        $this->addPagesToClear($configuration, $pagesToClear);

        if (
            isset($configuration['classes'])
            && is_array($configuration['classes'])
        ) {
            $resultClass = get_class($result[0]);

            foreach ($configuration['classes'] as $key=>$localConfig) {
                if ($key !== $resultClass) {
                    continue;
                }
                if ($this->shouldClearAll($localConfig)) {
                    $this->cacheService->clearPageCache();
                    return true;
                }

                $this->addPagesToClear($localConfig, $pagesToClear);
            }
        }

        if ((bool)$pagesToClear) {
            $this->cacheService->clearPageCache($pagesToClear);
        }

        return true;
    }

    /**
     * Tells whether all caches should be cleared
     * Returns true if $configuration['all'] can be interpreted as
     * 'true'
     *
     * @param $configuration
     * @return bool
     */
    protected function shouldClearAll($configuration)
    {
        return isset($configuration['all'])
        && (bool)$configuration['all'];
    }

    /**
     * @param $localConfig
     * @param $pagesToClear
     */
    protected function addPagesToClear($localConfig, &$pagesToClear)
    {
        if (isset($localConfig['pages'])) {
            $pages = GeneralUtility::intExplode(
                ',',
                $localConfig['pages'],
                true
            );
            $pagesToClear = array_unique(array_merge($pagesToClear, $pages));
        }
    }
}
