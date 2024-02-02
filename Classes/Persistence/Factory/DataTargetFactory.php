<?php

namespace CPSIT\T3importExport\Persistence\Factory;

use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\Factory\AbstractFactory;
use CPSIT\T3importExport\Factory\FactoryInterface;
use CPSIT\T3importExport\IdentifiableInterface;
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\MissingClassException;
use CPSIT\T3importExport\MissingInterfaceException;
use CPSIT\T3importExport\Persistence\DataTargetInterface;
use CPSIT\T3importExport\Persistence\DataTargetRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

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
class DataTargetFactory extends AbstractFactory implements FactoryInterface
{
    final public const DEFAULT_DATA_TARGET_CLASS = DataTargetRepository::class;

    protected PersistenceManagerInterface $persistenceManager;

    public function __construct(PersistenceManagerInterface $persistenceManager = null) {

        if ($persistenceManager === null) {
            $persistenceManager = (GeneralUtility::makeInstance(ObjectManager::class))
                ->get(PersistenceManagerInterface::class);
        }
        if (null !== $persistenceManager) {
            $this->persistenceManager = $persistenceManager;
        }
    }
    /**
     * Builds a factory object
     *
     * @param array $settings
     * @param string $identifier
     * @return DataTargetInterface
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     * @throws MissingInterfaceException
     */
    public function get(array $settings = [], $identifier = null): DataTargetInterface
    {
        $dataTargetClass = self::DEFAULT_DATA_TARGET_CLASS;
        if (isset($settings['class'])) {
            $dataTargetClass = $settings['class'];
        }
        if (!class_exists($dataTargetClass)) {
            throw new MissingClassException(
                'Missing target.class ' . $dataTargetClass .
                ' in configuration for import task ' . $identifier,
                1451043513
            );
        }
        if (!in_array(DataTargetInterface::class, class_implements($dataTargetClass))) {
            throw new MissingInterfaceException(
                'Missing interface in configuration for task ' . $identifier . ' Class ' . $dataTargetClass .
                ' does not implement required interface ' . DataTargetInterface::class . '.',
                1451045997
            );
        }
        $objectClass = null;
        if (isset($settings['object']['class'])) {
            $objectClass = $settings['object']['class'];
            if (!class_exists($objectClass)) {
                throw new MissingClassException(
                    'Missing class ' . $objectClass .
                    ' in configuration for task ' . $identifier,
                    1451043367
                );
            }
        }
        /** @var DataTargetInterface $target */
        $target = GeneralUtility::makeInstance(
            $dataTargetClass,
            $objectClass,
            null,
            $this->persistenceManager
        );
        if ($target instanceof IdentifiableInterface && isset($settings['identifier'])) {
            $target->setIdentifier($settings['identifier']);
        }

        if ($target instanceof ConfigurableInterface && isset($settings['config'])) {
            /** @var ConfigurableInterface $target */
            $target->setConfiguration($settings['config']);
        }

        return $target;
    }
}
