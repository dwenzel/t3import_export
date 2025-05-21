<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Persistence\Factory;

use CPSIT\ImportExportCore\ConfigurableInterface;
use CPSIT\T3importExport\Factory\AbstractFactory;
use CPSIT\T3importExport\Factory\FactoryInterface;
use CPSIT\T3importExport\IdentifiableInterface;
use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\ImportExportCore\Exception\MissingClassException;
use CPSIT\ImportExportCore\Exception\MissingInterfaceException;
use CPSIT\T3importExport\Persistence\DataSourceDB;
use CPSIT\ImportExportCore\Persistence\DataSourceInterface;
use CPSIT\T3importExport\RenderContentTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
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
class DataSourceFactory extends AbstractFactory implements FactoryInterface
{
    use RenderContentTrait;

    final public const string DEFAULT_DATA_SOURCE_CLASS = DataSourceDB::class;

    /**
     * Builds a DataSource object
     *
     * @param array $settings Configuration for the data source
     * @param string $identifier Identifier
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     * @throws MissingInterfaceException
     */
    public function get(array $settings = [], $identifier = null): DataSourceInterface
    {
        $dataSourceClass = self::DEFAULT_DATA_SOURCE_CLASS;
        if (isset($settings['class'])) {
            $dataSourceClass = $settings['class'];
        }
        if (!class_exists($dataSourceClass)) {
            throw new MissingClassException(
                'Missing source.class ' . $dataSourceClass . '.',
                1_451_060_913
            );
        }
        if (!in_array(DataSourceInterface::class, class_implements($dataSourceClass))) {
            throw new MissingInterfaceException(
                'Missing interface in configuration for source. Class ' . $dataSourceClass .
                ' must implement interface ' . DataSourceInterface::class . '.',
                1_451_061_361
            );
        }
        // fixme: We should test for implementation of ConfigurableInterface here and use an empty default config
        if (
            in_array(ConfigurableInterface::class, class_implements($dataSourceClass))
            && !isset($settings['config'])) {
            throw new InvalidConfigurationException(
                'Missing configuration option config for class ' .
                $dataSourceClass,
                1_451_086_595
            );
        }

        // note: we want an independend instance for each component
        /** @var DataSourceInterface $dataSource */
        $dataSource = clone GeneralUtility::makeInstance($dataSourceClass);
        if (
            in_array(IdentifiableInterface::class, class_implements($dataSourceClass), true)
            && isset($settings['identifier'])
        ) {
            /** @var IdentifiableInterface $dataSource */
            $dataSource->setIdentifier($settings['identifier']);
        }

        if (
            in_array(ConfigurableInterface::class, class_implements($dataSourceClass))
        ) {
            $dataSource->setConfiguration(
                $settings['config']
            );
        }

        return $dataSource;
    }
}
