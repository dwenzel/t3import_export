<?php

namespace CPSIT\T3importExport\Component\Initializer;

/***************************************************************
 *  Copyright notice
 *  (c) 2017 Jan-Henrik Hempel <hempel@motor.berlin>
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

use CPSIT\T3importExport\DatabaseTrait;
use Exception;
use TYPO3\CMS\Core\Database\Connection;

/**
 * Class UpdateTable
 * Updates records from a database table.
 * @package \CPSIT\T3importExport\Component\Initializer
 */
class UpdateTable extends AbstractInitializer implements InitializerInterface
{
    use DatabaseTrait;

    /**
     * @param array $configuration
     * @param array $records Array with prepared records
     * @return bool
     * @throws Exception
     */
    public function process(array $configuration, array &$records): bool
    {
        $table = $configuration[static::KEY_TABLE];
        $where = !empty($configuration[static::KEY_WHERE]) ? $configuration[static::KEY_WHERE] : ['1=1'];
        $data = $configuration[static::KEY_SET_FIELDS];
        $types = !empty($configuration[static::KEY_TYPES]) ? $configuration[static::KEY_TYPES] : [];
        $quoteTypes = [];

        foreach ($data as $key => $value) {
            // default is string
            $type = Connection::PARAM_STR;

            if ($types[$key] === 'int') {
                $type = Connection::PARAM_INT;
            }
            if ($types[$key] === 'bool') {
                $type = Connection::PARAM_BOOL;
            }
            if ($types[$key] === 'null') {
                $type = Connection::PARAM_NULL;
            }
            $quoteTypes[] = $type;
        }

        $this->connectionPool->getConnectionForTable($table)
            ->update($table, $data, $where, $quoteTypes);

        return true;
    }

    /**
     * Tells whether the given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration): bool
    {
        if (empty($configuration[static::KEY_TABLE])
            || !is_string($configuration[static::KEY_TABLE])
        ) {
            return false;
        }

        if (!empty($configuration[static::KEY_WHERE])
            && !is_array($configuration[static::KEY_WHERE])
        ) {
            return false;
        }

        if (!isset($configuration[static::KEY_SET_FIELDS])
            || !is_array($configuration[static::KEY_SET_FIELDS])
        ) {
            return false;
        }

        return true;
    }
}
