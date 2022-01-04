<?php

namespace CPSIT\T3importExport\Validation\Configuration;

use CPSIT\T3importExport\InvalidConfigurationException;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class MappingConfigurationValidator implements ConfigurationValidatorInterface
{
    /**
     * @param array $config
     * @return bool
     */
    public function validate(array $config)
    {

        return $this->validatePropertyConfiguration($config);
    }

    /**
     * @param array $configuration
     * @return bool
     * @throws \CPSIT\T3importExport\InvalidConfigurationException
     */
    protected function validatePropertyConfiguration(array $configuration)
    {
        if (isset($configuration['allowProperties'])
            && !is_string($configuration['allowProperties'])
        ) {
            throw new InvalidConfigurationException(
                'Invalid configuration for ' . __CLASS__ .
                '. Option value allowProperties must be a comma separated
                 string of property names.',
                1451146869
            );
        }
        if (isset($configuration['properties'])) {

            if (!is_array($configuration['properties'])
            ) {
                throw new InvalidConfigurationException(
                    'Invalid configuration for ' . __CLASS__ .
                    '. Option value properties must be an array.',
                    1451147517
                );
            }

            foreach ($configuration['properties'] as $propertyName => $localConfiguration) {
                $this->validatePropertyConfigurationRecursive($localConfiguration);
            }
        }

        return true;
    }

    /**
     * @param array $localConfiguration
     * @throws InvalidConfigurationException
     */
    protected function validatePropertyConfigurationRecursive(array $localConfiguration)
    {
        $this->validatePropertyConfiguration($localConfiguration);
        if (isset($localConfiguration['children'])) {
            if (!isset($localConfiguration['children']['maxItems'])) {
                throw new InvalidConfigurationException(
                    'Invalid configuration for ' . __CLASS__ .
                    '. children.maxItems must be set.',
                    1451157586
                );
            }
            foreach ($localConfiguration['children']['properties'] as $child => $childConfiguration) {
                $this->validatePropertyConfiguration($childConfiguration);
            }
        }
    }
}
