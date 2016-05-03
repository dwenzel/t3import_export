<?php
namespace CPSIT\T3importExport\Persistence\Factory;

use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\Factory\AbstractFactory;
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\MissingClassException;
use CPSIT\T3importExport\MissingInterfaceException;
use CPSIT\T3importExport\Persistence\DataTargetInterface;
use CPSIT\T3importExport\Persistence\DataTargetRepository;

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
class DataTargetFactory extends AbstractFactory
{
    const DEFAULT_DATA_TARGET_CLASS = DataTargetRepository::class;

    /**
     * Builds a factory object
     *
     * @param array $settings
     * @param string $identifier
     * @return DataTargetInterface
     * @throws \CPSIT\T3importExport\InvalidConfigurationException
     * @throws \CPSIT\T3importExport\MissingClassException
     * @throws MissingInterfaceException
     */
    public function get(array $settings, $identifier = null)
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
        if (!isset($settings['object']['class'])) {
            throw new InvalidConfigurationException(
                'Invalid configuration for import task ' . $identifier .
                ': target.object.class not set.',
                1451043340
            );
        }
        $objectClass = $settings['object']['class'];
        if (!class_exists($objectClass)) {
            throw new MissingClassException(
                'Missing class ' . $objectClass .
                ' in configuration for task ' . $identifier,
                1451043367
            );
        }
        /** @var DataTargetInterface $target */
        $target = $this->objectManager->get(
            $dataTargetClass,
            $objectClass
        );

        if ($target instanceof ConfigurableInterface && isset($settings['config'])) {
            /** @var ConfigurableInterface $target */
            $target->setConfiguration($settings['config']);
        }

        return $target;
    }

}
