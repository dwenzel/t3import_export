<?php /** @noinspection PhpParamsInspection */

namespace CPSIT\T3importExport\Tests\Unit\Component\Converter;

use CPSIT\T3importExport\Component\Converter\ArrayToDomainObject;
use CPSIT\T3importExport\Property\PropertyMappingConfigurationBuilder;
use CPSIT\T3importExport\Validation\Configuration\MappingConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TargetClassConfigurationValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
 * @package CPSIT\T3importExport\Tests\Unit\Component\Converter
 * @coversDefaultClass \CPSIT\T3importExport\Component\Converter\ArrayToDomainObject
 */
class ArrayToDomainObjectTest extends TestCase
{

    /**
     * @var ArrayToDomainObject
     */
    protected $subject;

    /**
     * @var PropertyMapper | MockObject
     */
    protected $propertyMapper;

    /**
     * @var PropertyMappingConfigurationBuilder|MockObject
     */
    protected $propertyMappingConfigurationBuilder;

    /**
     * @var TargetClassConfigurationValidator|MockObject
     */
    protected $targetClassConfigurationValidator;

    /**
     * @var MappingConfigurationValidator|MockObject
     */
    protected $mappingConfigurationValidator;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     *
     */
    public function setUp()
    {
        $this->objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();
        $this->propertyMapper = $this->getMockBuilder(PropertyMapper::class)
            ->setMethods(['convert'])
            ->getMock();
        $this->propertyMappingConfigurationBuilder = $this->getMockBuilder(PropertyMappingConfigurationBuilder::class)
            ->setMethods(['build'])
            ->getMock();
        $this->targetClassConfigurationValidator = $this->getMockBuilder(TargetClassConfigurationValidator::class)
            ->setMethods(['validate'])
            ->getMock();
        $this->mappingConfigurationValidator = $this->getMockBuilder(MappingConfigurationValidator::class)
            ->setMethods(['validate'])
            ->getMock();
        $this->subject = new ArrayToDomainObject(
            $this->propertyMapper,
            $this->propertyMappingConfigurationBuilder,
            $this->targetClassConfigurationValidator,
            $this->mappingConfigurationValidator
        );
        $this->subject->injectObjectManager($this->objectManager);
    }

    /**
     * @test
     * @noinspection PhpParamsInspection
     */
    public function getMappingConfigurationInitiallyReturnsDefaultPropertyMappingConfiguration()
    {
        $mockMappingConfiguration = $this->getMockBuilder(PropertyMappingConfiguration::class)
            ->setMethods(
                [
                    'setTypeConverterOptions',
                    'forProperty',
                    'forProperties',
                    'skipUnknownProperties',
                    'allowProperties'
                ]
            )->getMock();
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

        $this->objectManager->expects($this->once())
            ->method('get')
            ->with(PropertyMappingConfiguration::class)
            ->willReturn($mockMappingConfiguration);
        $this->assertSame(
            $mockMappingConfiguration,
            $this->subject->getMappingConfiguration([])
        );
    }


    /**
     * @test
     */
    public function getMappingConfigurationSetsTypeConverterOptions()
    {
        $mockMappingConfiguration = $this->getMockBuilder(PropertyMappingConfiguration::class)
            ->setMethods(
                [
                    'setTypeConverterOptions',
                    'forProperty',
                    'forProperties',
                    'skipUnknownProperties',
                    'allowProperties'
                ]
            )
            ->getMock();
        $this->objectManager->expects($this->once())
            ->method('get')
            ->with(PropertyMappingConfiguration::class)
            ->willReturn($mockMappingConfiguration);
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
                    PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true,
                    PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => true
                ]
            )
            ->will($this->returnValue($mockMappingConfiguration));

        $this->subject->getMappingConfiguration([]);
    }

    /**
     * @test
     */
    public function getMappingConfigurationReturnsMappingConfigurationIfSet()
    {
        $mappingConfiguration = $this->getMockBuilder(PropertyMappingConfiguration::class)->getMock();

        $this->subject->setPropertyMappingConfiguration($mappingConfiguration);
        $this->assertEquals(
            $mappingConfiguration,
            $this->subject->getMappingConfiguration([])
        );
    }

    /**
     * @test
     */
    public function getMappingConfigurationBuildsAndReturnsConfigurationForType()
    {
        $configuration = ['foo'];

        $mockMappingConfiguration = $this->getMockBuilder(PropertyMappingConfiguration::class)
            ->getMock();

        $this->propertyMappingConfigurationBuilder
            ->expects($this->once())
            ->method('build')
            ->with($configuration)
            ->willReturn($mockMappingConfiguration);

        $this->assertEquals(
            $mockMappingConfiguration,
            $this->subject->getMappingConfiguration($configuration)
        );
    }

    /**
     * @test
     */
    public function convertReturnsConvertedObject()
    {
        $record = [];
        $expectedObject = $this->getMockBuilder(DomainObjectInterface::class)
            ->getMockForAbstractClass();
        $configuration = [
            'targetClass' => 'FooClassName'
        ];
        $mappingConfiguration = $configuration;
        unset($mappingConfiguration['targetClass']);

        $mockMappingConfiguration = $this->getMockBuilder(PropertyMappingConfiguration::class)
            ->getMock();
        $this->subject->setPropertyMappingConfiguration($mockMappingConfiguration);
        $this->propertyMapper->expects($this->once())
            ->method('convert')
            ->with(
                $record,
                $configuration['targetClass'],
                $mockMappingConfiguration
            )
            ->will($this->returnValue($expectedObject));

        $this->assertSame(
            $expectedObject,
            $this->subject->convert($record, $configuration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidValidatesTargetClassAndMappingConfiguration()
    {
        $config = ['foo'];
        $this->targetClassConfigurationValidator->expects($this->once())
            ->method('validate')
            ->with($config)
            ->willReturn(true);

        $this->mappingConfigurationValidator->expects($this->once())
            ->method('validate')
            ->with($config)
            ->willReturn(true);

        $this->assertTrue(
            $this->subject->isConfigurationValid($config)
        );
    }
}
