<?php
namespace CPSIT\T3importExport\Tests\Unit\Component\Converter;

use CPSIT\T3importExport\Component\Converter\ArrayToDomainObject;
use CPSIT\T3importExport\Property\PropertyMappingConfigurationBuilder;
use CPSIT\T3importExport\Validation\Configuration\MappingConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TargetClassConfigurationValidator;
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
 * @package CPSIT\T3importExport\Tests\Unit\Component\Converter
 * @coversDefaultClass \CPSIT\T3importExport\Component\Converter\ArrayToDomainObject
 */
class ArrayToDomainObjectTest extends UnitTestCase
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
        $this->subject = $this->getAccessibleMock(
            ArrayToDomainObject::class,
            ['dummy']
        );
    }

    /**
     * @test
     * @covers ::injectPropertyMapper
     */
    public function injectPropertyMapperForObjectSetsPropertyMapper()
    {
        /** @var PropertyMapper $mockPropertyMapper */
        $mockPropertyMapper = $this->getMock(PropertyMapper::class,
            [], [], '', false);

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
    public function injectPropertyMappingConfigurationBuilderForObjectSetsPropertyMappingConfigurationBuilder()
    {
        /** @var PropertyMappingConfigurationBuilder $mockPropertyMappingConfigurationBuilder */
        $mockPropertyMappingConfigurationBuilder = $this->getMock(PropertyMappingConfigurationBuilder::class,
            [], [], '', false);

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
    public function injectObjectManagerForObjectSetsObjectManager()
    {
        /** @var ObjectManager $mockObjectManager */
        $mockObjectManager = $this->getMock(ObjectManager::class,
            [], [], '', false);

        $this->subject->injectObjectManager($mockObjectManager);

        $this->assertSame(
            $mockObjectManager,
            $this->subject->_get('objectManager')
        );
    }

    /**
     * @test
     */
    public function injectTargetClassConfigurationValidatorSetsValidator()
    {
        $mockValidator = $this->getAccessibleMock(
            TargetClassConfigurationValidator::class
        );
        $this->subject->injectTargetClassConfigurationValidator($mockValidator);

        $this->assertAttributeSame(
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
        $mockValidator = $this->getAccessibleMock(
            MappingConfigurationValidator::class
        );
        $this->subject->injectMappingConfigurationValidator($mockValidator);

        $this->assertAttributeSame(
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
    public function getMappingConfigurationSetsTypeConverterOptions()
    {
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
    public function getMappingConfigurationBuildsAndReturnsConfigurationForType()
    {
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
    public function convertGetsMappingConfiguration()
    {
        $this->subject = $this->getAccessibleMock(
            ArrayToDomainObject::class,
            ['getMappingConfiguration', 'emitSignal']
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
    public function convertReturnsConvertedObject()
    {
        $record = [];
        $this->subject = $this->getAccessibleMock(
            ArrayToDomainObject::class,
            ['getMappingConfiguration', 'emitSignal']
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
     */
    public function isConfigurationValidValidates()
    {
        $mockTargetClassValidator = $this->getAccessibleMock(
            TargetClassConfigurationValidator::class,
            ['validate']
        );
        $mockMappingConfigurationValidator = $this->getAccessibleMock(
            MappingConfigurationValidator::class,
            ['validate']
        );
        $config = ['foo'];
        $this->subject->injectTargetClassConfigurationValidator($mockTargetClassValidator);
        $this->subject->injectMappingConfigurationValidator($mockMappingConfigurationValidator);

        $mockTargetClassValidator->expects($this->once())
            ->method('validate')
            ->with($config)
            ->will($this->returnValue(true));

        $mockMappingConfigurationValidator->expects($this->once())
            ->method('validate')
            ->with($config)
            ->will($this->returnValue(true));

        $this->assertTrue(
            $this->subject->isConfigurationValid($config)
        );
    }
}
