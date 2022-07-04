<?php
namespace CPSIT\T3importExport\Component\PreProcessor;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ArrayUtility;

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
class StringToTime extends AbstractPreProcessor implements PreProcessorInterface
{

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration): bool
    {
        if (!isset($configuration['fields'])) {
            return false;
        }

        return true;
    }

    /**
     * @param array $configuration
     * @param array $record
     * @return TRUE
     */
    public function process($configuration, &$record)
    {
        $this->fields = GeneralUtility::trimExplode(',', $configuration['fields'], true);
        $this->convertFields($record);
        if (isset($configuration['multipleRowFields'])) {
            $multipleRowFields = GeneralUtility::trimExplode(',', $configuration['multipleRowFields'], true);
            foreach ($multipleRowFields as $field) {
                if (is_array($record[$field])) {
                    foreach ($record[$field] as $key => $row) {
                        $this->convertFields($row);
                        $record[$field][$key] = $row;
                    }
                }
            }
        }

        return true;
    }

    protected function convertFields(&$record): void
    {
        foreach ($this->fields as $fieldName) {
            $record[$fieldName] = $this->stringToTime($record[$fieldName]);
        }
    }

    /**
     * convert a string ito a datetime - if not possible or not string, return null
     * hint: many times this field comes as an array
     */
    protected function stringToTime(/*mixed*/ $recordField): ?int
    {
        if (isset($recordField) && is_string($recordField)) {
            return strtotime($recordField) ? : null;
        }

        return null;
    }
}
