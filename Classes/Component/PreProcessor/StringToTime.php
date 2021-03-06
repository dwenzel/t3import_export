<?php
namespace CPSIT\T3importExport\Component\PreProcessor;

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
    public function isConfigurationValid(array $configuration)
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
        $this->fields = ArrayUtility::trimExplode(',', $configuration['fields'], true);
        $this->convertFields($record);
        if (isset($configuration['multipleRowFields'])) {
            $multipleRowFields = ArrayUtility::trimExplode(',', $configuration['multipleRowFields'], true);
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

    /**
     * @param $row
     */
    protected function convertFields(&$row)
    {
        foreach ($this->fields as $fieldName) {
            if (isset($row[$fieldName]) && is_string($row[$fieldName])) {
                $row[$fieldName] = strtotime($row[$fieldName]);
            }
        }
    }
}
