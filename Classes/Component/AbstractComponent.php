<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Component;

use CPSIT\ImportExportCore\ConfigurableInterface;
use CPSIT\ImportExportCore\ConfigurableTrait;
use CPSIT\ImportExportCore\Domain\Model\TaskResult;
use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\T3importExport\Expression\ExpressionEvaluatorTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

/**
 * Class AbstractComponent
 */
abstract class AbstractComponent implements ConfigurableInterface
{
    use ConfigurableTrait;
    use ExpressionEvaluatorTrait;

    final public const int CONTENT_OBJECT_NOT_SUPPORTED_EXCEPTION_CODE = 1_794_781_862;

    /**
     * Tells if the component is disabled
     *
     * `disable` may be
     * - `1` or `true`
     * - an array with `if.result.hasMessage` (comma separated message ids)
     * - an array with `expression`: evaluated with the variables `record` and `result`
     *
     * Content objects (`disable = TEXT`) are not supported any more since 4.0.
     *
     * @throws InvalidConfigurationException if `disable` is a content object
     */
    public function isDisabled(array $configuration, array $record = [], ?TaskResult $result = null): bool
    {
        if (!isset($configuration['disable'])) {
            return false;
        }

        $disable = $configuration['disable'];
        if ($disable === true || $disable === 1 || $disable === '1') {
            return true;
        }
        if (is_array($disable)) {
            if (isset($disable['_typoScriptNodeValue'])) {
                throw new InvalidConfigurationException(
                    'Disabling components with TypoScript content objects is not supported any more. Use disable.expression instead.',
                    self::CONTENT_OBJECT_NOT_SUPPORTED_EXCEPTION_CODE
                );
            }
            if (isset($disable['if']['result']['hasMessage'])) {
                $messageIds = GeneralUtility::intExplode(
                    ',',
                    (string)$disable['if']['result']['hasMessage'],
                    true
                );
                foreach ($messageIds as $id) {
                    if ($result instanceof TaskResult && $result->hasMessageWithId($id)) {
                        return true;
                    }
                }
            }

            if (isset($disable['expression'])) {
                return $this->getExpressionEvaluator()->isTrue(
                    (string)$disable['expression'],
                    ['record' => $record, 'result' => $result]
                );
            }
        }

        return false;
    }
}
