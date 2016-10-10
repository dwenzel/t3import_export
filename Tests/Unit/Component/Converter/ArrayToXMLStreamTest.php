<?php
namespace CPSIT\T3importExport\Tests\Unit\Component\Converter;

use CPSIT\T3importExport\Component\Converter\ArrayToXMLStream;
use CPSIT\T3importExport\Domain\Model\DataStream;
use CPSIT\T3importExport\Property\PropertyMappingConfigurationBuilder;
use CPSIT\T3importExport\Validation\Configuration\MappingConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TargetClassConfigurationValidator;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;

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
 * @package CPSIT\T3importExport\Tests\Unit\Component\Converter
 * @coversDefaultClass \CPSIT\T3importExport\Component\Converter\ArrayToDomainObject
 */
class ArrayToXMLStreamTest extends UnitTestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|ArrayToXMLStream
	 */
	protected $subject;

	/**
	 *
	 */
	public function setUp() {
		$this->subject = $this->getAccessibleMock(
			ArrayToXMLStream::class,
			['dummy']
		);
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|ObjectManager
	 */
	protected function injectObjectManager()
	{
		/** @var ObjectManager $mockObjectManager */
		$mockObjectManager = $this->getMock(ObjectManager::class,
			[], [], '', FALSE);

		$this->subject->injectObjectManager($mockObjectManager);

		$this->assertSame(
			$mockObjectManager,
			$this->subject->_get('objectManager')
		);

		return $mockObjectManager;
	}

	protected function injectPropertyMapperObject()
	{
		/** @var PropertyMapper $mockPropertyMapper */
		$mockPropertyMapper = $this->getAccessibleMock(
			PropertyMapper::class,
			['dummy']
		);

		$this->subject->injectPropertyMapper($mockPropertyMapper);

		$this->assertSame(
			$mockPropertyMapper,
			$this->subject->_get('propertyMapper')
		);

		return $mockPropertyMapper;
	}

	protected function injectPropertyMappingConfigurationBuilderObject()
	{


		/** @var PropertyMappingConfigurationBuilder $mockPropertyMappingBuilder */
		$mockPropertyMappingBuilder = $this->getAccessibleMock(
			PropertyMappingConfigurationBuilder::class,
			['dummy']
		);

		$this->subject->injectPropertyMappingConfigurationBuilder($mockPropertyMappingBuilder);

		$this->assertSame(
			$mockPropertyMappingBuilder,
			$this->subject->_get('propertyMappingConfigurationBuilder')
		);

		return $mockPropertyMappingBuilder;
	}

	protected function injectTargetClassConfigurationValidatorObject()
	{
		/** @var TargetClassConfigurationValidator $MockTargetValidator */
		$MockTargetValidator = $this->getAccessibleMock(
			TargetClassConfigurationValidator::class,
			['dummy']
		);

		$this->subject->injectTargetClassConfigurationValidator($MockTargetValidator);

		$this->assertSame(
			$MockTargetValidator,
			$this->subject->_get('targetClassConfigurationValidator')
		);

		return $MockTargetValidator;
	}

	protected function injectMappingConfigurationValidatorObject()
	{
		/** @var MappingConfigurationValidator $configurationValidator */
		$configurationValidator = $this->getAccessibleMock(
			MappingConfigurationValidator::class,
			['dummy']
		);

		$this->subject->injectMappingConfigurationValidator($configurationValidator);

		$this->assertSame(
			$configurationValidator,
			$this->subject->_get('mappingConfigurationValidator')
		);

		return $configurationValidator;
	}

	/**
	 * @test
	 */
	public function getMappingConfigurationTest()
	{
		// test for default configurator
		$mockedObjectManager = $this->injectObjectManager();
		$mockPropertyMapping = $this->getMock(PropertyMappingConfiguration::class,
			['setTypeConverterOptions', 'skipUnknownProperties'], [], '', FALSE);
		$mockPropertyMapping->expects($this->once())
			->method('setTypeConverterOptions')
			->will($this->returnValue($mockPropertyMapping));

		$mockedObjectManager->expects($this->once())
			->method('get')
			->with(PropertyMappingConfiguration::class)
			->will($this->returnValue($mockPropertyMapping));

		$configurator = $this->subject->getMappingConfiguration();

		$this->assertSame(
			$mockPropertyMapping,
			$configurator
		);

		// test storage
		$configurator = $this->subject->getMappingConfiguration();

		$this->assertSame(
			$mockPropertyMapping,
			$configurator
		);

		/*
		$this->injectMappingConfigurationValidatorObject();
		$this->injectPropertyMapperObject();
		$this->injectPropertyMappingConfigurationBuilderObject();
		$this->injectTargetClassConfigurationValidatorObject();*/
	}

	/**
	 * @test
	 */
	public function isConfigurationValidValidatesTargetClass()
	{
		/** @var TargetClassConfigurationValidator|\PHPUnit_Framework_MockObject_MockObject $mockedTargetValidator */
		$mockedTargetValidator = $this->getMock(
			TargetClassConfigurationValidator::class, ['validate']
		);
		$this->subject->injectTargetClassConfigurationValidator($mockedTargetValidator);

		/** @var MappingConfigurationValidator|\PHPUnit_Framework_MockObject_MockObject $mockedMappingValidator */
		$mockedMappingValidator = $this->getMock(
			MappingConfigurationValidator::class
		);
		$this->subject->injectMappingConfigurationValidator($mockedMappingValidator);


		$config = [
			'targetClass' => "CPSIT\\T3importExport\\Domain\\Model\\DataStream"
		];
		
		$mockedTargetValidator->expects($this->once())
			->method('validate')
			->with($config);
		$this->subject->isConfigurationValid($config);
	}

	/**
	 * @test
	 */
	public function isConfigurationValidValidatesMappingClass()
	{
		/** @var TargetClassConfigurationValidator|\PHPUnit_Framework_MockObject_MockObject $mockedTargetValidator */
		$mockedTargetValidator = $this->getMock(
			TargetClassConfigurationValidator::class, ['validate']
		);
		$this->subject->injectTargetClassConfigurationValidator($mockedTargetValidator);


		/** @var MappingConfigurationValidator|\PHPUnit_Framework_MockObject_MockObject $mockedMappingValidator */
		$mockedMappingValidator = $this->getMock(
			MappingConfigurationValidator::class,  ['validate']
		);
		$this->subject->injectMappingConfigurationValidator($mockedMappingValidator);


		$config = [
			'targetClass' => "CPSIT\\T3importExport\\Domain\\Model\\DataStream"
		];

		$mockedTargetValidator->expects($this->once())
			->method('validate')
			->with($config)
			->willReturn(true);
		$mockedMappingValidator->expects($this->once())
			->method('validate')
			->with($config);
		$this->subject->isConfigurationValid($config);
	}

	/**
	 * @test
	 */
	public function defaultConfigurationRootEnclosure()
	{
		$testArray = ['value' => 'a'];
		$testConfig = [
			'targetClass' => DataStream::class
		];
		$resultObject = new DataStream();

		$objectManager = $this->injectObjectManager();
		$objectManager->expects($this->once())
			->method('get')
			->with(DataStream::class)
			->willReturn($resultObject);

		/** @var DataStream $result */
		$result = $this->subject->convert($testArray, $testConfig);
		$this->assertSame($resultObject, $result);
		$this->assertEquals($result->getSteamBuffer(), '<row><value>a</value></row>');

	}

	/**
	 * @test
	 */
	public function customConfigurationRootEnclosure()
	{
		$testArray = ['value' => 'a'];
		$testConfig = [
			'targetClass' => DataStream::class,
			'nodeName' => 'unitTest'
		];
		$resultObject = new DataStream();

		$objectManager = $this->injectObjectManager();
		$objectManager->expects($this->once())
			->method('get')
			->with(DataStream::class)
			->willReturn($resultObject);

		/** @var DataStream $result */
		$result = $this->subject->convert($testArray, $testConfig);
		$this->assertSame($resultObject, $result);
		$expected = '<unitTest><value>a</value></unitTest>';
		$this->assertEquals($expected, $result->getSteamBuffer());

	}

	/**
	 * @test
	 */
	public function mappingXMLGeneration()
	{
		$testArray = [
			'value' => 'a',
			'@mapTo' => 'unitTest',
			'childNodeArray' => [
				'@mapTo' => 'customSubNode',
				'v' => 'a'
			],
			'childs' => [
				'@mapTo' => 'someChilds',
				[
					'@mapTo' => 'child',
					'v' => 'a'
				],
				[
					'@mapTo' => 'child',
					'v' => 'a'
				]
			]
		];
		$testConfig = [
			'targetClass' => DataStream::class
		];
		$resultObject = new DataStream();

		$objectManager = $this->injectObjectManager();
		$objectManager->expects($this->once())
			->method('get')
			->with(DataStream::class)
			->willReturn($resultObject);

		/** @var DataStream $result */
		$result = $this->subject->convert($testArray, $testConfig);
		$this->assertSame($resultObject, $result);

		$expected = '<unitTest>
			<value>a</value>
			<customSubNode>
				<v>a</v>
			</customSubNode>
			<someChilds>
				<child>
					<v>a</v>
				</child>
				<child>
					<v>a</v>
				</child>
			</someChilds>
		</unitTest>';
		$expected = preg_replace("/\r|\n|\t/", "", $expected);
		$this->assertEquals($expected, $result->getSteamBuffer());
	}

	/**
	 * @test
	 */
	public function attributeXMLGeneration()
	{
		$testArray = [
			'value' => 'a',
			'@attribute' => [
				'key' => '1',
				'otherKey' => '2'
			],
			'childNodeArray' => [
				'v' => 'a',
				'@attribute' => [
					'key' => '1',
					'otherKey' => '2'
				]
			],
			'childs' => [
				'@attribute' => [
					'key' => '1'
				],
				[
					'v' => 'a',
					'@attribute' => [
						'key' => '1'
					]
				],
				[
					'v' => 'a'
				]
			]
		];
		$testConfig = [
			'targetClass' => DataStream::class
		];
		$resultObject = new DataStream();

		$objectManager = $this->injectObjectManager();
		$objectManager->expects($this->once())
			->method('get')
			->with(DataStream::class)
			->willReturn($resultObject);

		/** @var DataStream $result */
		$result = $this->subject->convert($testArray, $testConfig);
		$this->assertSame($resultObject, $result);

		$expected = '<row key="1" otherKey="2">
						<value>a</value>
						<childNodeArray key="1" otherKey="2">
							<v>a</v>
						</childNodeArray>
						<childs key="1">
							<row key="1">
								<v>a</v>
							</row>
							<row>
								<v>a</v>
							</row>
						</childs>
					</row>';
		$expected = preg_replace("/\r|\n|\t/", "", $expected);
		$this->assertEquals($expected, $result->getSteamBuffer());
	}
}
