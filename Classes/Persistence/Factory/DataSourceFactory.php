<?php
namespace CPSIT\T3import\Persistence\Factory;

use CPSIT\T3import\Factory\AbstractFactory;
use CPSIT\T3import\IdentifiableInterface;
use CPSIT\T3import\Persistence\DataSourceDB;
use CPSIT\T3import\Persistence\DataSourceInterface;
use CPSIT\T3import\Persistence\MissingClassException;
use CPSIT\T3import\Persistence\MissingInterfaceException;
use CPSIT\T3import\Service\InvalidConfigurationException;

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
class DataSourceFactory extends AbstractFactory {
	const DEFAULT_DATA_SOURCE_CLASS = DataSourceDB::class;

	/**
	 * Builds a DataSource object
	 *
	 * @param string $identifier Identifier
	 * @param array $settings Configuration for the data source
	 * @throws MissingClassException
	 * @throws MissingInterfaceException
	 * @throws InvalidConfigurationException
	 * @return DataSourceInterface
	 */
	public function get($identifier, array $settings) {
		$dataSourceClass = self::DEFAULT_DATA_SOURCE_CLASS;
		if (isset($settings['class'])) {
			$dataSourceClass = $settings['class'];
		}
		if (!class_exists($dataSourceClass)) {
			throw new MissingClassException(
				'Missing source.class ' . $dataSourceClass .
				' in configuration for ' . $identifier,
				1451060913
			);
		}
		if (!in_array(DataSourceInterface::class, class_implements($dataSourceClass))) {
			throw new MissingInterfaceException(
				'Missing interface in configuration for '. $identifier . '.source. Class ' . $dataSourceClass .
				' must implement interface ' . DataSourceInterface::class . '.',
				1451061361
			);
		}
		if (!isset($settings['config'])) {
			throw new InvalidConfigurationException(
				'Missing ' . $identifier . '.source.config for class ' .
				$dataSourceClass,
				1451086595
			);
		}

		if (in_array(IdentifiableInterface::class, class_implements($dataSourceClass))) {
			// dataSourceClass implements IdentifiableInterface but identifier is not set
			if (!isset($settings['identifier'])) {
				throw new InvalidConfigurationException(
					'Invalid configuration for ' . $identifier .
					'.source. Missing identifier for class ' . $dataSourceClass . '.',
					1451083802
				);
			}
			/** @var IdentifiableInterface $dataSource */
			$dataSource = $this->objectManager->get($dataSourceClass);
			$dataSource->setIdentifier($settings['identifier']);

			return $dataSource;
		}

		$dataSource = $this->objectManager->get($dataSourceClass);
		return $dataSource;
	}
}