<?php
namespace CPSIT\T3importExport\Component\PreProcessor;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Dirk Wenzel <wenzel@cps-it.de>
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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class MapObjectToArray
 * Maps objects implementing the DomainObjectInterface into an array
 * Intended as convenience parent class for import/export task.
 * Should be refactored into base extension t3import_export.
 */
class MapObjectToArray extends AbstractPreProcessor implements PreProcessorInterface
{
    /**
     * Static field values. Those values must be set in configuration.
     */
    const REQUIRED_CONFIGURATION_FIELDS = [];

    /**
     * Key value pairs for mapping of object values
     * to keys in the result array. Both in dot notation.
     * 'target.path' => 'object.propertyPath'
     * Note: Order matters for some tags.
     */
    const ENTITY_VALUE_MAP = [];

    /**
     * Format for date values
     */
    const DATE_FORMAT = 'Y-m-d';

    /**
     * Format for time values
     */
    const TIME_FORMAT = 'H:i:s';

    /**
     * value 'yes'
     */
    const VALUE_YES = 'yes';

    /**
     * value 'no'
     */
    const VALUE_NO = 'no';

    /**
     * key for fields configuration
     */
    const CONFIGURATION_KEY_FIELDS = 'fields';

    /**
     * key for map configuration
     */
    const CONFIGURATION_KEY_MAP = 'map';

    /**
     * Maps a domain object to an array
     *
     * @param array $configuration
     * @param DomainObjectInterface|array $record
     * @return bool
     * @throws \RuntimeException
     */
    public function process($configuration, &$record)
    {
        $result = [];

        foreach ($this->getRequiredFields() as $requiredField) {
            $result[$requiredField] = $this->getFieldValueFromConfiguration($configuration, $requiredField);
        }

        $overrideMap = [];
        if (!empty($configuration[static::CONFIGURATION_KEY_MAP])) {
            $overrideMap = $configuration[static::CONFIGURATION_KEY_MAP];
        }

        foreach ($this->getValueMap($overrideMap) as $key => $objectPath) {
            $value = $this->getObjectPropertyPath($record, $objectPath);
            $result = ArrayUtility::setValueByPath($result, $key, $value);
        }

        $record = $result;
        return true;
    }

    /**
     * Gets the value map.
     * Initially returns the value class constant ENTITY_VALUE_MAP.
     * (which can be overridden by child classes)
     * If $override is a non-empty array it will be merged with the
     * class constant value.
     * @param array $override Overriding array.
     * @return array
     */
    public function getValueMap(array $override = []) {
        $map = static::ENTITY_VALUE_MAP;
        ArrayUtility::mergeRecursiveWithOverrule($map, $override);

        return $map;
    }

    /**
     * Returns required fields for mapping
     * @return array
     */
    public function getRequiredFields() {
        return static::REQUIRED_CONFIGURATION_FIELDS;
    }

    /**
     * Gets a value for a field from configuration.
     * @param array $configuration Configuration array to read from.
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getFieldValueFromConfiguration($configuration, $key, $default = '')
    {
        if (
            !isset($configuration[static::CONFIGURATION_KEY_FIELDS])
            || empty($configuration[static::CONFIGURATION_KEY_FIELDS][$key])
        ) {
            return $default;
        }

        return $configuration[static::CONFIGURATION_KEY_FIELDS][$key];
    }

    protected function sortByArbitraryKeys(&$inputArray, $sortOrder)
    {
        $sortOrder = array_flip($sortOrder);
        uksort($inputArray, function ($a, $b) use ($sortOrder) {
            return $sortOrder[$a] - $sortOrder[$b];
        });
    }

    /**
     * Gets the property value of an entity object by path.
     * This method is a wrapper for Extbase' static ObjectAccess.
     * LazyObjectStorage properties are converted to arrays.
     * For LazyLoadingProxy properties the real instance is returned.
     * @param DomainObjectInterface $entity
     * @param $path
     * @param string|null $default
     * @return mixed
     */
    protected function getObjectPropertyPath($entity, $path, $default = null)
    {
        $value = ObjectAccess::getPropertyPath($entity, $path);
        if (empty($value)) {
            return $default;
        }

        if ($value instanceof LazyObjectStorage) {
            return $value->toArray();
        }
        if ($value instanceof LazyLoadingProxy) {
            $value = $value->_loadRealInstance();
        }

        return $value;
    }
}
