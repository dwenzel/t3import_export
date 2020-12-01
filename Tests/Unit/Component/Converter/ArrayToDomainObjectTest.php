<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\Converter;

use CPSIT\T3importExport\Component\Converter\ArrayToDomainObject;
use CPSIT\T3importExport\Property\PropertyMappingConfigurationBuilder;
use CPSIT\T3importExport\Validation\Configuration\MappingConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TargetClassConfigurationValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Tests\UnitTestCase;
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
     *
     */
    public function setUp()
    {
        $this->subject = $this->getMockBuilder(ArrayToDomainObject::class)
            ->setMethods(['dummy'])
            ->getMock();
    }

    /**
     * @test
     * @covers ::injectPropertyMapper
     */
    public function injectPropertyMapperForObjectSetsPropertyMapper()
    {
        /** @var PropertyMapper $mockPropertyMapper */
        $mockPropertyMapper = $this->getMockBuilder(PropertyMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subject->injectPropertyMapper($mockPropertyMapper);

        self::assertObjectHasAttribute(
            'propertyMapper',
            $this->subject
        );
    }

    /**
     * @test
     * @covers ::injectPropertyMappingConfigurationBuilder
     */
    public function injectPropertyMappingConfigurationBuilderForObjectSetsPropertyMappingConfigurationBuilder()
    {
        /** @var PropertyMappingConfigurationBuilder $mockPropertyMappingConfigurationBuilder */
        $mockPropertyMappingConfigurationBuilder = $this->getMockBuilder(PropertyMappingConfigurationBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject->injectPropertyMappingConfigurationBuilder($mockPropertyMappingConfigurationBuilder);

        self::assertObjectHasAttribute(
            'propertyMappingConfigurationBuilder',
            $this->subject
        );
    }

    /**
     * @test
     * @covers ::injectObjectManager
     */
    public function injectObjectManagerForObjectSetsObjectManager()
    {
        /** @var ObjectManager $mockObjectManager */
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject->injectObjectManager($mockObjectManager);

        self::assertObjectHasAttribute(
            'objectManager',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function injectTargetClassConfigurationValidatorSetsValidator()
    {
        /** @var TargetClassConfigurationValidator|MockObject $mockValidator */
        $mockValidator = $this->getMockBuilder(TargetClassConfigurationValidator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject->injectTargetClassConfigurationValidator($mockValidator);

        self::assertAttributeSame(
            $mockValidator,
            'targetClassConfigurationValidator',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function injectMappingConfigurationValidatorSetsValidator()
    {
        $mockValidator = $this->getMockBuilder(MappingConfigurationValidator::class)
            ->getMock();
        $this->subject->injectMappingConfigurationValidator($mockValidator);

        self::assertAttributeSame(
            $mockValidator,
            'mappingConfigurationValidator',
            $this->subject
        );
    }

    /**
     * @test
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
            )
            ->getMock();
        $mockMappingConfiguration->expects(self::any())
            ->method('setTypeConverterOptions')
            ->will(self::returnValue($mockMappingConfiguration));
        $mockMappingConfiguration->expects(self::any())
            ->method('skipUnknownProperties')
            ->will(self::returnValue($mockMappingConfiguration));
        $mockMappingConfiguration->expects(self::any())
            ->method('forProperty')
            ->will(self::returnValue($mockMappingConfiguration));
        $mockMappingConfiguration->expects(self::any())
            ->method('allowProperties')
            ->will(self::returnValue($mockMappingConfiguration));

        /** @var ObjectManager|MockObject $mockObjectManager */
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])
            ->getMock();
        $this->subject->injectObjectManager($mockObjectManager);

        $mockObjectManager->expects(self::once())
            ->method('get')
            ->with(PropertyMappingConfiguration::class)
            ->will(self::returnValue($mockMappingConfiguration));
        self::assertSame(
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
            ->setMethods(['skipUnknownProperties', 'forProperty', 'allowProperties', 'setTypeConverterOptions' ])
            ->getMock();

        /** @var ObjectManager|MockObject $mockObjectManager */
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])
            ->getMock();

        $this->subject->injectObjectManager($mockObjectManager);

        $mockObjectManager->expects(self::once())
            ->method('get')
            ->with(PropertyMappingConfiguration::class)
            ->will(self::returnValue($mockMappingConfiguration));
        $mockMappingConfiguration->expects(self::any())
            ->method('skipUnknownProperties')
            ->will(self::returnValue($mockMappingConfiguration));
        $mockMappingConfiguration->expects(self::any())
            ->method('forProperty')
            ->will(self::returnValue($mockMappingConfiguration));
        $mockMappingConfiguration->expects(self::any())
            ->method('allowProperties')
            ->will(self::returnValue($mockMappingConfiguration));

        $mockMappingConfiguration->expects(self::any())
            ->method('setTypeConverterOptions')
            ->with(
                PersistentObjectConverter::class,
                [
                    PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true,
                    PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => true
                ]
            )
            ->will(self::returnValue($mockMappingConfiguration));

        $this->subject->getMappingConfiguration([]);
    }

    public function testPropertyMappingConfigurationCanBeSet()
    {
        /** @var PropertyMappingConfiguration|MockObject $mappingConfiguration */
        $mappingConfiguration = $this->getMockBuilder(PropertyMappingConfiguration::class)
            ->getMock();

        $this->subject->setPropertyMappingConfiguration($mappingConfiguration);
        self::assertEquals(
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

        /** @var PropertyMappingConfigurationBuilder|MockObject $mockMappingConfigurationBuilder */
        $mockMappingConfigurationBuilder = $this->getMockBuilder(PropertyMappingConfigurationBuilder::class)
            ->setMethods(['build'])
            ->getMock();
        $this->subject->injectPropertyMappingConfigurationBuilder(
            $mockMappingConfigurationBuilder
        );
        $mockMappingConfiguration = $this->getMockBuilder(PropertyMappingConfiguration::class)
            ->getMock();

        $mockMappingConfigurationBuilder->expects(self::once())
            ->method('build')
            ->with($configuration)
            ->will(self::returnValue($mockMappingConfiguration));

        self::assertEquals(
            $mockMappingConfiguration,
            $this->subject->getMappingConfiguration($configuration)
        );
    }

    /**
     * @test
     */
    public function convertGetsMappingConfiguration()
    {
        $this->subject = $this->getMockBuilder(ArrayToDomainObject::class)
            ->setMethods(['getMappingConfiguration', 'emitSignal'])
            ->getMock();
        $configuration = [
            'targetClass' => 'FooClassName'
        ];
        $mappingConfiguration = $configuration;
        unset($mappingConfiguration['targetClass']);
        /** @var PropertyMapper|MockObject $mockPropertyMapper */
        $mockPropertyMapper = $this->getMockBuilder(PropertyMapper::class)
            ->setMethods(['convert'])
            ->getMock();

        $record = [];
        $this->subject->injectPropertyMapper($mockPropertyMapper);

        $this->subject->expects(self::once())
            ->method('getMappingConfiguration')
            ->with($mappingConfiguration);

        $this->subject->convert($record, $configuration);
    }

    /**
     * @test
     */
    public function convertReturnsConvertedObject()
    {
        $record = [];
        $this->subject = $this->getMockBuilder(ArrayToDomainObject::class)
            ->setMethods(['getMappingConfiguration', 'emitSignal'])
            ->getMock();
        $expectedObject = $this->getMockForAbstractClass(DomainObjectInterface::class);
        $configuration = [
            'targetClass' => 'FooClassName'
        ];
        $mappingConfiguration = $configuration;
        unset($mappingConfiguration['targetClass']);

        $mockPropertyMapper = $this->getMockBuilder(PropertyMapper::class)
            ->setMethods(['convert'])
            ->getMock();
        $mockMappingConfiguration = $this->getMockBuilder(PropertyMappingConfiguration::class)
            ->getMock();
        $this->subject->expects(self::once())
            ->method('getMappingConfiguration')
            ->with($mappingConfiguration)
            ->will(self::returnValue($mockMappingConfiguration));
        $mockPropertyMapper->expects(self::once())
            ->method('convert')
            ->with(
                $record,
                $configuration['targetClass'],
                $mockMappingConfiguration
            )
            ->will(self::returnValue($expectedObject));
        $this->subject->injectPropertyMapper($mockPropertyMapper);

        self::assertSame(
            $expectedObject,
            $this->subject->convert($record, $configuration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidValidates()
    {
        /** @var TargetClassConfigurationValidator|MockObject $mockTargetClassValidator */
        $mockTargetClassValidator = $this->getMockBuilder(TargetClassConfigurationValidator::class)
         ->setMethods(['validate'])
            ->getMock();
        /** @var MappingConfigurationValidator|MockObject $mockMappingConfigurationValidator */
        $mockMappingConfigurationValidator = $this->getMockBuilder(MappingConfigurationValidator::class)
            ->setMethods(['validate'])
            ->getMock();

        $config = ['foo'];
        $this->subject->injectTargetClassConfigurationValidator($mockTargetClassValidator);
        $this->subject->injectMappingConfigurationValidator($mockMappingConfigurationValidator);

        $mockTargetClassValidator->expects(self::once())
            ->method('validate')
            ->with($config)
            ->will(self::returnValue(true));

        $mockMappingConfigurationValidator->expects(self::once())
            ->method('validate')
            ->with($config)
            ->will(self::returnValue(true));

        self::assertTrue(
            $this->subject->isConfigurationValid($config)
        );
    }
}
