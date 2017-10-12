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
use CPSIT\T3importExport\Service\DatabaseConnectionService;

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
     */
    public function process($configuration, &$records)
    {
        if (isset($configuration['identifier'])) {
            $this->database = $this->connectionService
                ->getDatabase($configuration['identifier']);
        }
        $table = $configuration['table'];
        $where = $configuration['where'];
		$setfields = $configuration['setfields'];

        return (bool)$this->database->exec_UPDATEquery($table, $where, $setfields);
    }

    /**
     * Tells whether the given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        if (!isset($configuration['table'])
            || !is_string($configuration['table'])
        ) {
            return false;
        }

        if (!isset($configuration['where'])
            || !is_string($configuration['where'])
        ) {
            return false;
        }

		if (!isset($configuration['setfields'])
		    || !is_array($configuration['setfields'])
		) {
		    return false;
		}

        if (isset($configuration['identifier'])
            and !DatabaseConnectionService::isRegistered($configuration['identifier'])
        ) {
            return false;
        }

        return true;
    }
}
