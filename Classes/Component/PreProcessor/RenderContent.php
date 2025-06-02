<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Component\PreProcessor;

use CPSIT\ImportExportCore\Component\PreProcessor\PreProcessorInterface;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

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
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;

class RenderContent extends AbstractPreProcessor implements PreProcessorInterface
{
    public function __construct(
        protected ContentObjectRenderer $contentObjectRenderer,
        protected TypoScriptService $typoScriptService
    ) {}

    /**
     * Override trait method to use injected dependencies
     */
    public function getContentObjectRenderer(): ContentObjectRenderer
    {
        return $this->contentObjectRenderer;
    }

    /**
     * Override trait method to use injected dependencies
     */
    public function getTypoScriptService(): TypoScriptService
    {
        return $this->typoScriptService;
    }

    /**
     * Override the trait's renderContent method to use injected dependencies
     * @param array $record Optional data array
     * @param array $configuration Plain or TypoScript array
     * @return mixed|null Returns rendered content for each valid TypoScript object or null.
     * @throws ContentRenderingException
     */
    public function renderContent(array $record, array $configuration): mixed
    {
        $typoScriptConf = $this->typoScriptService
            ->convertPlainArrayToTypoScriptArray($configuration);
        /** @var \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject $contentObject */
        $contentObject = $this->contentObjectRenderer
            ->getContentObject($configuration['_typoScriptNodeValue']);

        if ($contentObject !== null) {
            $this->contentObjectRenderer->start($record);

            return $contentObject->render($typoScriptConf);
        }

        return null;
    }

    /**
     * @param array $configuration
     * @param array $record
     * @return bool
     * @throws ContentRenderingException
     */
    public function process(array $configuration, array &$record): bool
    {
        $this->renderFields($configuration, $record);

        return true;
    }

    /**
     * Tells whether a given configuration is valid
     */
    #[\Override]
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
