<?php

namespace CPSIT\T3importExport\Tests\Property;

use CPSIT\T3importExport\Property\PropertyMappingConfigurationBuilder;
use CPSIT\T3importExport\Tests\Unit\Traits\MockObjectManagerTrait;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;

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

/**
 * Class PropertyMappingConfigurationBuilderTest
 *
 * @package CPSIT\T3importExport\Tests\Property
 * @coversDefaultClass \CPSIT\T3importExport\Property\PropertyMappingConfigurationBuilder
 */
class PropertyMappingConfigurationBuilderTest extends TestCase
{
    protected PropertyMappingConfigurationBuilder $subject;
    protected PropertyMappingConfiguration $propertyMappingConfiguration;

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function setUp(): void
    {
        $this->subject = new PropertyMappingConfigurationBuilder();
        $this->propertyMappingConfiguration = $this->getMockBuilder(PropertyMappingConfiguration::class)
            ->setMethods(
                [
                    'allowAllProperties',
                    'allowProperties',
                    'setTypeConverterOptions',
                    'skipUnknownProperties',
                ]
            )
            ->getMock();
        GeneralUtility::addInstance(PropertyMappingConfiguration::class, $this->propertyMappingConfiguration);
    }

    public function tearDown(): void
    {
        GeneralUtility::purgeInstances();
    }

    /**
     * @test
     */
    public function buildSetsDefaultTypeConverterClassAndOptions(): void
    {
        $configuration = [];
        $defaultTypeConverterClass = PersistentObjectConverter::class;
        $defaultTypeConverterOptions = [
            PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true,
            PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => true
        ];
        $this->propertyMappingConfiguration->expects($this->once())
            ->method('setTypeConverterOptions')
            ->with(...[$defaultTypeConverterClass, $defaultTypeConverterOptions]);

        $this->subject->build($configuration);
    }

    /**
     * @test
     */
    public function buildSetsTypeConverterClassFromConfiguration(): void
    {
        $typeConverterClass = 'foo';
        $configuration = [
            'typeConverter' => [
                'class' => $typeConverterClass
            ]
        ];
        $defaultTypeConverterOptions = [
            PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true,
            PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => true
        ];

        $this->propertyMappingConfiguration->expects($this->once())
            ->method('setTypeConverterOptions')
            ->with(...[$typeConverterClass, $defaultTypeConverterOptions]);

        $this->subject->build($configuration);
    }

    /**
     * @test
     */
    public function buildSetsTypeConverterOptionsFromConfiguration(): void
    {
        $typeConverterOptions = [
            PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => false,
            PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => true
        ];
        $configuration = [
            'typeConverter' => [
                'options' => $typeConverterOptions
            ]
        ];
        $defaultTypeConverterClass = PersistentObjectConverter::class;
        GeneralUtility::addInstance(PropertyMappingConfiguration::class, $this->propertyMappingConfiguration);

        $this->propertyMappingConfiguration->expects($this->once())
            ->method('setTypeConverterOptions')
            ->with(...[$defaultTypeConverterClass, $typeConverterOptions]);

        $this->subject->build($configuration);
    }

    /**
     * @test
     */
    public function buildSetsSkipUnknownProperties(): void
    {
        $configuration = [];

        $this->propertyMappingConfiguration->expects($this->once())
            ->method('skipUnknownProperties');

        $this->subject->build($configuration);
    }

    /**
     * @test
     */
    public function buildSetsAllowPropertiesFromConfiguration(): void
    {
        $configuration = [
            'allowProperties' => 'foo,bar'
        ];
        GeneralUtility::addInstance(PropertyMappingConfiguration::class, $this->propertyMappingConfiguration);

        $this->propertyMappingConfiguration->expects($this->once())
            ->method('allowProperties')
            ->with(...['foo', 'bar']);

        $this->subject->build($configuration);
    }

    /**
     * @test
     */
    public function buildInitiallyDoesNotSetsAllowProperties(): void
    {
        $configuration = [];

        $this->propertyMappingConfiguration->expects($this->never())
            ->method('allowProperties');

        $this->subject->build($configuration);
    }

    /**
     * @test
     * @covers ::getProperties
     */
    public function getPropertiesInitiallyReturnsEmptyArray(): void
    {
        $configuration = [];
        $expectedResult = [];
        self::assertSame(
            $expectedResult,
            $this->subject->getProperties($configuration)
        );
    }


    /**
     * @test
     * @covers ::getProperties
     */
    public function getPropertiesInitiallyReturnsPropertiesFromConfiguration(): void
    {
        $configuration = [
            'properties' => [
                'foo' => []
            ]
        ];
        $expectedResult = [
            'foo' => []
        ];

        $this->assertEquals(
            $expectedResult,
            $this->subject->getProperties($configuration)
        );
    }

    /**
     * @test
     * @covers ::build
     */
    public function buildConfiguresAllowedProperties(): void
    {
        $this->subject = $this->getMockBuilder(PropertyMappingConfigurationBuilder::class)
            ->setMethods(['configure'])
            ->getMock();
        $configuration = [
            'allowProperties' => 'foo',
            'properties' => [
                'foo' => [

                ]
            ]
        ];
        $this->subject->expects($this->once())
            ->method('configure')
            ->with(...[$configuration, $this->propertyMappingConfiguration]);

        $this->subject->build($configuration);
    }

    /**
     * @test
     */
    public function buildInitiallyDoesNotAllowAllProperties(): void
    {
        $configuration = [];
        $this->propertyMappingConfiguration->expects($this->never())
            ->method('allowAllProperties');

        $this->subject->build($configuration);
    }

    /**
     * @test
     */
    public function buildInitiallySetsAllowAllProperties(): void
    {
        $configuration = [
            'allowAllProperties' => 1
        ];

        $this->propertyMappingConfiguration->expects($this->once())
            ->method('allowAllProperties');

        $this->subject->build($configuration);
    }
}
