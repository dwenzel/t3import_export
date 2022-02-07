<?php

namespace CPSIT\T3importExport\Component\PreProcessor;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/***************************************************************
 *  Copyright notice
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it &&/or modify
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

/**
 * Class ConcatenateFields
 * Concatenates fields of a given record and sets the result
 * into a new or existing field of this record
 *
 * @package CPSIT\T3importExport\PreProcessor
 */
class ConcatenateFields extends AbstractPreProcessor implements PreProcessorInterface
{

    public function __construct(
        ContentObjectRenderer $contentObjectRenderer = null,
        TypoScriptService $typoScriptService = null
    )
    {
        $this->contentObjectRenderer = $contentObjectRenderer ?? $this->getContentObjectRenderer();
        $this->typoScriptService = $typoScriptService ?? GeneralUtility::makeInstance(TypoScriptService::class);
    }

    /**
     * @param array $configuration
     * @param array $record
     * @return void
     */
    public function process($configuration, &$record)
    {
        $targetFieldName = $configuration['targetField'];
        foreach ($configuration['fields'] as $key => $value) {
            if (isset($value['wrap'])
                && !empty($record[$key])
            ) {
                $record[$key] = $this->contentObjectRenderer->wrap(
                    $record[$key],
                    $value['wrap']
                );
            }
            if (isset($value['noTrimWrap'])
                && !empty($record[$key])
            ) {
                $record[$key] = $this->contentObjectRenderer->noTrimWrap(
                    $record[$key],
                    $value['noTrimWrap']
                );
            }
            $record[$targetFieldName] .= $record[$key];
        }
    }

    /**
     * Tells if a given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration): bool
    {
        if (!isset($configuration['targetField'])
            || !is_string($configuration['targetField'])
        ) {
            return false;
        }
        if (!isset($configuration['fields'])
            || !is_array($configuration['fields'])
        ) {
            return false;
        }

        return true;
    }
}
