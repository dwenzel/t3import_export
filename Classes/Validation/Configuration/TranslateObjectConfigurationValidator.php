<?php

namespace CPSIT\T3importExport\Validation\Configuration;

use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\MissingClassException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2022 Dirk Wenzel <wenzel@cps-it.de>
 *  All rights reserved
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class TranslateObjectConfigurationValidator implements ConfigurationValidatorInterface
{
    protected const KEY_ARGUMENTS = 'arguments';

    final public const VALIDATORS = [
        [
            ConfigurationValidatorInterface::KEY_CLASS => IssetValidator::class,
            self::KEY_ARGUMENTS => [self::KEY_PARENT_FIELD]
        ],
        [
            ConfigurationValidatorInterface::KEY_CLASS => IssetValidator::class,
            self::KEY_ARGUMENTS => [self::KEY_LANGUAGE]
        ],
        [
            ConfigurationValidatorInterface::KEY_CLASS => NotEmptyValidator::class,
            self::KEY_ARGUMENTS => []
        ]
    ];

    /**
     * @var TargetClassConfigurationValidator
     */
    protected $targetClassConfigurationValidator;

    /**
     * @var MappingConfigurationValidator
     */
    protected $mappingConfigurationValidator;


    public function __construct(
        TargetClassConfigurationValidator $targetClassConfigurationValidator = null,
        MappingConfigurationValidator $mappingConfigurationValidator = null
    )
    {
        $this->targetClassConfigurationValidator = $targetClassConfigurationValidator ?? GeneralUtility::makeInstance(TargetClassConfigurationValidator::class);
        $this->mappingConfigurationValidator = $mappingConfigurationValidator ?? GeneralUtility::makeInstance(MappingConfigurationValidator::class);
    }

    /**
     * @inheritDoc
     *
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     */
    public function isValid(array $config): bool
    {
        $validatorSettings = static::VALIDATORS;

        return (
            $this->isBasicConfigurationValid($validatorSettings, $config)
            && $this->isMappingConfigurationValid($config)
        );
    }

    /**
     * @param array $validatorSettings
     * @param array $config
     * @return bool
     */
    protected function isBasicConfigurationValid(array $validatorSettings, array $config): bool
    {
        foreach ($validatorSettings as $settings) {
            if (!(isset($settings[self::KEY_CLASS])
                && isset($settings[self::KEY_ARGUMENTS]))) {
                continue;
            }
            $className = $settings[self::KEY_CLASS];
            $validator = new $className(...$settings[self::KEY_ARGUMENTS]);
            if ($validator->isValid($config)) {
                continue;
            }
            return false;
        }
        return true;
    }

    /**
     * @param array $config
     * @return bool
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     */
    protected function isMappingConfigurationValid(array $config): bool
    {
        if (isset($config[static::KEY_MAPPING])) {
            $mappingConfiguration = $config[static::KEY_MAPPING];
            if (isset($mappingConfiguration[static::KEY_TARGET_CLASS])
                && !$this->targetClassConfigurationValidator->isValid($mappingConfiguration)) {
                return false;
            }
            if (isset($mappingConfiguration['config'])
                && !$this->mappingConfigurationValidator->isValid($mappingConfiguration)) {
                return false;
            }
        }

        return true;
    }
}
