<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Validation\Configuration;

use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\ImportExportCore\Exception\MissingClassException;

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
class SetL10nParentConfigurationValidator implements ConfigurationValidatorInterface
{
    protected const KEY_ARGUMENTS = 'arguments';
    protected const KEY_SUBJECT = 'subject';

    final public const array VALIDATORS = [
        [
            ConfigurationValidatorInterface::KEY_CLASS => IssetValidator::class,
            self::KEY_ARGUMENTS => [self::KEY_SUBJECT . '/' . self::KEY_PARENT_FIELD],
        ],
        [
            ConfigurationValidatorInterface::KEY_CLASS => IssetValidator::class,
            self::KEY_ARGUMENTS => [self::KEY_PARENT . '/' . self::KEY_TABLE],
        ],
        [
            ConfigurationValidatorInterface::KEY_CLASS => IssetValidator::class,
            self::KEY_ARGUMENTS => [self::KEY_PARENT . '/' . self::KEY_MATCH_FIELD],
        ],
        [
            ConfigurationValidatorInterface::KEY_CLASS => NotEmptyValidator::class,
            self::KEY_ARGUMENTS => [self::KEY_PARENT . '/' . self::KEY_IDENTITY_FIELD],
        ],
    ];

    /**
     * @inheritDoc
     *
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     */
    public function isValid(array $config): bool
    {
        return $this->isBasicConfigurationValid(static::VALIDATORS, $config);
    }

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
}
