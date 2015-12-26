<?php
namespace CPSIT\T3import\Tests\Unit\Component\Converter;

use CPSIT\T3import\Component\Converter\ArrayToDomainObject;
use CPSIT\T3import\Property\PropertyMappingConfigurationBuilder;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;

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

/**
 * Class ArrayToDomainObjectTest
 *
 * @package CPSIT\T3import\Tests\Unit\Component\Converter
 * @coversDefaultClass \CPSIT\T3import\Component\Converter\ArrayToDomainObject
 */
class ArrayToDomainObjectTest extends UnitTestCase {

	/**
	 * @var ArrayToDomainObject
	 */
	protected $subject;

	/**
	 *
	 */
	public function setUp() {
		$this->subject = $this->getAccessibleMock(
			ArrayToDomainObject::class,
			['dummy']
		);
	}

	/**
	 * @test
	 * @covers ::injectPropertyMapper
	 */
	public function injectPropertyMapperForObjectSetsPropertyMapper() {
		/** @var PropertyMapper $mockPropertyMapper */
		$mockPropertyMapper = $this->getMock(PropertyMapper::class,
			[], [], '', FALSE);

		$this->subject->injectPropertyMapper($mockPropertyMapper);

		$this->assertSame(
			$mockPropertyMapper,
			$this->subject->_get('propertyMapper')
		);
	}

	/**
	 * @test
	 * @covers ::injectPropertyMappingConfigurationBuilder
	 */
	public function injectPropertyMappingConfigurationBuilderForObjectSetsPropertyMappingConfigurationBuilder() {
		/** @var PropertyMappingConfigurationBuilder $mockPropertyMappingConfigurationBuilder */
		$mockPropertyMappingConfigurationBuilder = $this->getMock(PropertyMappingConfigurationBuilder::class,
			[], [], '', FALSE);

		$this->subject->injectPropertyMappingConfigurationBuilder($mockPropertyMappingConfigurationBuilder);

		$this->assertSame(
			$mockPropertyMappingConfigurationBuilder,
			$this->subject->_get('propertyMappingConfigurationBuilder')
		);
	}

	/**
	 * @test
	 * @covers ::injectObjectManager
	 */
	public function injectObjectManagerForObjectSetsObjectManager() {
		/** @var ObjectManager $mockObjectManager */
		$mockObjectManager = $this->getMock(ObjectManager::class,
			[], [], '', FALSE);

		$this->subject->injectObjectManager($mockObjectManager);

		$this->assertSame(
			$mockObjectManager,
			$this->subject->_get('objectManager')
		);
	}

	/**
	 * @test
	 */
	public function getMappingConfigurationInitiallyReturnsDefaultPropertyMappingConfiguration() {
		$mockMappingConfiguration = $this->getMock(
			PropertyMappingConfiguration::class,
			[
				'setTypeConverterOptions',
				'forProperty',
				'forProperties',
				'skipUnknownProperties',
				'allowProperties'
			]
		);
		$mockMappingConfiguration->expects($this->any())
			->method('setTypeConverterOptions')
			->will($this->returnValue($mockMappingConfiguration));
		$mockMappingConfiguration->expects($this->any())
			->method('skipUnknownProperties')
			->will($this->returnValue($mockMappingConfiguration));
		$mockMappingConfiguration->expects($this->any())
			->method('forProperty')
			->will($this->returnValue($mockMappingConfiguration));
		$mockMappingConfiguration->expects($this->any())
			->method('allowProperties')
			->will($this->returnValue($mockMappingConfiguration));

		$mockObjectManager = $this->getMock(ObjectManager::class,
			['get']);
		$this->subject->injectObjectManager($mockObjectManager);

		$mockObjectManager->expects($this->once())
			->method('get')
			->with(PropertyMappingConfiguration::class)
			->will($this->returnValue($mockMappingConfiguration));
		$this->assertSame(
			$mockMappingConfiguration,
			$this->subject->getMappingConfiguration([])
		);
	}


	/**
	 * @test
	 */
	public function getMappingConfigurationSetsTypeConverterOptions() {
		$mockMappingConfiguration = $this->getMock(
			'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration',
			[
				'setTypeConverterOptions',
				'forProperty',
				'forProperties',
				'skipUnknownProperties',
				'allowProperties'
			]
		);
		$mockObjectManager = $this->getMock(ObjectManager::class,
			['get']);
		$this->subject->injectObjectManager($mockObjectManager);

		$mockObjectManager->expects($this->once())
			->method('get')
			->with(PropertyMappingConfiguration::class)
			->will($this->returnValue($mockMappingConfiguration));
		$mockMappingConfiguration->expects($this->any())
			->method('skipUnknownProperties')
			->will($this->returnValue($mockMappingConfiguration));
		$mockMappingConfiguration->expects($this->any())
			->method('forProperty')
			->will($this->returnValue($mockMappingConfiguration));
		$mockMappingConfiguration->expects($this->any())
			->method('allowProperties')
			->will($this->returnValue($mockMappingConfiguration));

		$mockMappingConfiguration->expects($this->any())
			->method('setTypeConverterOptions')
			->with(
				PersistentObjectConverter::class,
				[
					PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE,
					PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => TRUE
				]
			)
			->will($this->returnValue($mockMappingConfiguration));

		$this->subject->getMappingConfiguration([]);
	}

	/**
	 * @test
	 */
	public function getMappingConfigurationReturnsMappingConfigurationIfSet() {
		$mappingConfiguration = $this->getMock(
			PropertyMappingConfiguration::class
		);

		$this->subject->_set('propertyMappingConfiguration', $mappingConfiguration);
		$this->assertEquals(
			$mappingConfiguration,
			$this->subject->getMappingConfiguration([])
		);
	}

	/**
	 * @test
	 */
	public function getMappingConfigurationBuildsAndReturnsConfigurationForType() {
		$configuration = ['foo'];

		$mockMappingConfigurationBuilder = $this->getMock(
			PropertyMappingConfigurationBuilder::class,
			['build']
		);
		$this->subject->injectPropertyMappingConfigurationBuilder(
			$mockMappingConfigurationBuilder
		);
		$mockMappingConfiguration = $this->getMock(
			PropertyMappingConfiguration::class
		);

		$mockMappingConfigurationBuilder->expects($this->once())
			->method('build')
			->with($configuration)
			->will($this->returnValue($mockMappingConfiguration));

		$this->assertEquals(
			$mockMappingConfiguration,
			$this->subject->getMappingConfiguration($configuration)
		);
	}

	/**
	 * @test
	 */
	public function convertGetsMappingConfiguration() {
		$this->subject = $this->getAccessibleMock(
			ArrayToDomainObject::class,
			['getMappingConfiguration']
		);
		$configuration = [
			'targetClass' => 'FooClassName'
		];
		$mappingConfiguration = $configuration;
		unset($mappingConfiguration['targetClass']);
		$mockPropertyMapper = $this->getMock(
			PropertyMapper::class, ['convert']
		);
		$record = [];
		$this->subject->injectPropertyMapper($mockPropertyMapper);

		$this->subject->expects($this->once())
			->method('getMappingConfiguration')
			->with($mappingConfiguration);

		$this->subject->convert($record, $configuration);
	}

	/**
	 * @test
	 */
	public function convertReturnsConvertedObject() {
		$record = [];
		$this->subject = $this->getAccessibleMock(
			ArrayToDomainObject::class,
			['getMappingConfiguration']
		);
		$expectedObject = $this->getMock(
			DomainObjectInterface::class
		);
		$configuration = [
			'targetClass' => 'FooClassName'
		];
		$mappingConfiguration = $configuration;
		unset($mappingConfiguration['targetClass']);
		$mockPropertyMapper = $this->getMock(
			PropertyMapper::class, ['convert']
		);
		$mockMappingConfiguration = $this->getMock(
			PropertyMappingConfiguration::class
		);
		$this->subject->expects($this->once())
			->method('getMappingConfiguration')
			->with($mappingConfiguration)
			->will($this->returnValue($mockMappingConfiguration));
		$mockPropertyMapper->expects($this->once())
			->method('convert')
			->with(
				$record,
				$configuration['targetClass'],
				$mockMappingConfiguration
			)
			->will($this->returnValue($expectedObject));
		$this->subject->injectPropertyMapper($mockPropertyMapper);

		$this->assertSame(
			$expectedObject,
			$this->subject->convert($record, $configuration)
		);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\Service\InvalidConfigurationException
	 * @expectedExceptionCode 1451146126
	 */
	public function isConfigurationValidThrowsExceptionIfTargetClassIsNotSet() {
		$configuration = ['foo'];
		$this->subject->isConfigurationValid($configuration);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\Service\InvalidConfigurationException
	 * @expectedExceptionCode 1451146384
	 */
	public function isConfigurationValidThrowsExceptionIfTargetClassIsNotString() {
		$configuration = [
			'targetClass' => 1
		];
		$this->subject->isConfigurationValid($configuration);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\Persistence\MissingClassException
	 * @expectedExceptionCode 1451146564
	 */
	public function isConfigurationValidThrowsExceptionIfTargetClassDoesNotExist() {
		$configuration = [
			'targetClass' => 'NonExistingClassName'
		];
		$this->subject->isConfigurationValid($configuration);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\Service\InvalidConfigurationException
	 * @expectedExceptionCode 1451146869
	 */
	public function isConfigurationValidThrowsExceptionIfAllowPropertiesIsNotString() {
		$configuration = [
			'targetClass' => AbstractDomainObject::class,
			'allowProperties' => []
		];
		$this->subject->isConfigurationValid($configuration);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\Service\InvalidConfigurationException
	 * @expectedExceptionCode 1451147517
	 */
	public function isConfigurationValidThrowsExceptionIfPropertiesIsNotArray() {
		$configuration = [
			'targetClass' => AbstractDomainObject::class,
			'properties' => 'invalidStringValue'
		];
		$this->subject->isConfigurationValid($configuration);
	}

	/**
	 * @test
	 */
	public function isConfigurationValidValidatedPropertiesRecursive() {
		$this->subject = $this->getAccessibleMock(
			ArrayToDomainObject::class,
			['validatePropertyConfigurationRecursive']
		);
		$configuration = [
			'targetClass' => AbstractDomainObject::class,
			'properties' => [
				'propertyA' => [
					'allowAllProperties' => 1
				]
			]
		];

		$this->subject->expects($this->once())
			->method('validatePropertyConfigurationRecursive')
			->with($configuration['properties']['propertyA']);

		$this->subject->isConfigurationValid($configuration);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\Service\InvalidConfigurationException
	 * @expectedExceptionCode 1451157586
	 */
	public function validatePropertyConfigurationRecursiveThrowsExceptionIfMaxItemsIsNotSet() {
		$configuration = [
			'children' => [
				'propertyA' => ['allowAllProperties' => 1]
			]
		];
		$this->subject->_call(
			'validatePropertyConfigurationRecursive',
			$configuration);
	}

	/**
	 * @test
	 */
	public function validatePropertyConfigurationRecursiveDoesRecur() {
		$this->subject = $this->getAccessibleMock(
			ArrayToDomainObject::class,
			['validatePropertyConfiguration']
		);
		$configuration = [
			'children' => [
				'maxItems' => 1,
				'properties' => [
					'propertyA' => [
						'allowAllProperties' => 1
					]
				]
			]
		];
		$this->subject->expects($this->exactly(2))
			->method('validatePropertyConfiguration')
			->withConsecutive(
				[$configuration],
				[$configuration['children']['properties']['propertyA']]
			);
		$this->subject->_call(
			'validatePropertyConfigurationRecursive',
			$configuration);
	}}