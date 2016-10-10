<?php

namespace CPSIT\T3importExport\Persistence;

use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\ConfigurableTrait;
use CPSIT\T3importExport\DatabaseTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

/**
 * Copyright notice
 * (c) 2016. Vladimir Falcón Piva <falcon@cps-it.de>
 * All rights reserved
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * Class DataTargetDB
 * Persists records into a database.
 *
 * @package CPSIT\T3importExport\Persistence
 */
class DataTargetDB
    implements DataTargetInterface, ConfigurableInterface
{
    use ConfigurableTrait, DatabaseTrait;

    public function isConfigurationValid(array $configuration)
    {
        if (!isset($configuration['table'])) {
            return false;
        }

        if (isset($configuration['unsetKeys'])
        && !is_string($configuration['unsetKeys']))
        {
            return false;
        }

        return true;
    }

    /**
     * Persists an object into the database
     * If the record has a key '__identity' it will be
     * updated otherwise inserted as new record
     * If $configuration contains a key 'unsetKeys' it
     * will be handled as comma separated list of keys.
     * Any of those keys will be unset before persisting.
     *
     * @param array|DomainObjectInterface $object
     * @param array $configuration
     * @return bool
     */
    public function persist($object, array $configuration = null)
    {
        $tableName = $configuration['table'];

        if (isset($configuration['unsetKeys'])) {
            $unsetKeys = GeneralUtility::trimExplode(',', $configuration['unsetKeys'], true);
            if ((bool)$unsetKeys) {
                foreach ($unsetKeys as $key) {
                    unset($object[$key]);
                }
            }
        }

        if (isset($object['__identity'])) {
            $uid = $object['__identity'];
            unset($object['__identity']);
            $this->database->exec_UPDATEquery(
                $tableName,
                'uid = ' . $uid,
                $object
            );

            return true;
        }

        $this->database->exec_INSERTquery(
            $tableName,
            $object
        );

        return true;
    }

    /**
     * Dummy method
     * Currently does'nt do anything
     *
     * @param null $result
     * @param array|null $configuration
     * @return void
     */
    public function persistAll($result = null, array $configuration = null)
    {}


}
