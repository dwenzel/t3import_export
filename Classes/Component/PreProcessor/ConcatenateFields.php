<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Component\PreProcessor;

use CPSIT\ImportExportCore\Component\PreProcessor\PreProcessorInterface;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;

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
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class ConcatenateFields
 * Concatenates fields of a given record and sets the result
 * into a new or existing field of this record
 */
class ConcatenateFields extends AbstractPreProcessor implements PreProcessorInterface
{
    /**
     * @param ContentObjectRenderer|null $contentObjectRenderer not used anymore
     * @param TypoScriptService|null $typoScriptService not used anymore
     * @deprecated since 3.1, the arguments are ignored and will be removed in 4.0
     */
    public function __construct(
        ?ContentObjectRenderer $contentObjectRenderer = null,
        ?TypoScriptService $typoScriptService = null
    ) {}

    /**
     * @param array $configuration
     * @param array $record
     */
    public function process(array $configuration, array &$record): bool
    {
        $targetFieldName = $configuration['targetField'];
        $record[$targetFieldName] ??= '';
        foreach ($configuration['fields'] as $key => $value) {
            if (isset($value['wrap'])
                && !empty($record[$key])
            ) {
                $record[$key] = $this->wrap((string)$record[$key], (string)$value['wrap']);
            }
            if (isset($value['noTrimWrap'])
                && !empty($record[$key])
            ) {
                $record[$key] = $this->noTrimWrap((string)$record[$key], (string)$value['noTrimWrap']);
            }
            $record[$targetFieldName] .= $record[$key] ?? '';
        }

        return true;
    }

    /**
     * Wraps the content, as TypoScript `wrap`: `before|after`, parts trimmed
     */
    protected function wrap(string $content, string $wrap): string
    {
        if ($wrap === '') {
            return $content;
        }
        $parts = explode('|', $wrap);

        return trim($parts[0]) . $content . trim($parts[1] ?? '');
    }

    /**
     * Wraps the content, as TypoScript `noTrimWrap`: `|before|after|`, whitespace kept
     */
    protected function noTrimWrap(string $content, string $wrap): string
    {
        if ($wrap === '') {
            return $content;
        }
        $parts = explode('|', $wrap, 4);

        return ($parts[1] ?? '') . $content . ($parts[2] ?? '');
    }

    /**
     * Tells if a given configuration is valid
     */
    #[\Override]
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
