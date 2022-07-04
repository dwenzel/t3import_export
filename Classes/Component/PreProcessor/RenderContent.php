<?php
namespace CPSIT\T3importExport\Component\PreProcessor;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;

/***************************************************************
 *  Copyright notice
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
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
class RenderContent extends AbstractPreProcessor implements PreProcessorInterface
{
    public function __construct(
        ContentObjectRenderer $contentObjectRenderer = null,
        TypoScriptService $typoScriptService = null)
    {
        $this->contentObjectRenderer = $contentObjectRenderer ?? $this->getContentObjectRenderer();
        $this->typoScriptService = $typoScriptService ?? GeneralUtility::makeInstance(TypoScriptService::class);
    }

    /**
     * @param array $configuration
     * @param array $record
     * @return bool
     */
    public function process($configuration, &$record)
    {
        $this->renderFields($configuration, $record);

        return true;
    }

    /**
     * Tells whether a given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration): bool
    {
        if (!isset($configuration['fields'])) {
            return false;
        }
        if (!is_array($configuration['fields'])) {
            return false;
        }
        foreach ($configuration['fields'] as $field => $value) {
            if (!is_array($value)
                || empty($value)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $configuration
     * @param $record
     * @return array
     * @throws ContentRenderingException
     */
    protected function renderFields($configuration, &$record): ?array
    {
        foreach ($configuration['fields'] as $fieldName => $localConfiguration) {
            if (isset($localConfiguration['multipleRows'])) {
                $childRecords = $record[$fieldName];
                if (!is_array($childRecords)) {
                    continue;
                }
                foreach ($childRecords as $key => &$childRecord) {
                    $this->renderFields($localConfiguration, $childRecord);
                }
                unset($childRecord);

                $record[$fieldName] = $childRecords;
            } elseif (isset($localConfiguration['singleRow'])) {
                $record[$fieldName] = $this->renderFields($localConfiguration, $record[$fieldName]);
            } else {
                $record[$fieldName] = $this->renderContent($record, $localConfiguration);
            }
        }
        return $record;
    }
}
