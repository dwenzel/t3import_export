<?php
namespace CPSIT\T3importExport\Tests\Property;

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationBuilder;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\CMS\Extbase\Utility\ArrayUtility;

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
class PropertyMappingConfigurationBuilderTest extends UnitTestCase
{

    /**
     * @var \CPSIT\T3importExport\Property\PropertyMappingConfigurationBuilder
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            'CPSIT\\T3importExport\\Property\\PropertyMappingConfigurationBuilder',
            ['dummy'], [], '', false);
    }

    /**
     * @test
     * @covers ::injectObjectManager
     */
    public function injectObjectManagerForObjectSetsObjectManager()
    {
        /** @var ObjectManager $mockObjectManager */
        $mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            [], [], '', false);

        $this->subject->injectObjectManager($mockObjectManager);

        $this->assertSame(
            $mockObjectManager,
            $this->subject->_get('objectManager')
        );
    }

    /**
     * @test
     * @covers ::build
     */
    public function buildReturnsPropertyMappingConfiguration()
    {
        $mockMappingConfiguration = $this->getMock(
            'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration'
        );
        /** @var \PHPUnit_Framework_MockObject_MockObject $objectManager */
        $mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            ['get']);
        $this->subject->injectObjectManager($mockObjectManager);
        $configuration = [];
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->with('TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration')
            ->will($this->returnValue($mockMappingConfiguration));
        $this->assertSame(
            $mockMappingConfiguration,
            $this->subject->build($configuration)
        );
    }

    /**
     * @test
     */
    public function buildSetsDefaultTypeConverterClassAndOptions()
    {
        $mockMappingConfiguration = $this->getMock(
            'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration',
            ['setTypeConverterOptions'], [], '', false
        );
        $configuration = [];
        $defaultTypeConverterClass = 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\PersistentObjectConverter';
        $defaultTypeConverterOptions = [
            PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true,
            PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => true
        ];
        /** @var \PHPUnit_Framework_MockObject_MockObject $objectManager */
        $mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            ['get']);
        $this->subject->injectObjectManager($mockObjectManager);
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($mockMappingConfiguration));

        $mockMappingConfiguration->expects($this->once())
            ->method('setTypeConverterOptions')
            ->with($defaultTypeConverterClass, $defaultTypeConverterOptions);

        $this->subject->build($configuration);
    }

    /**
     * @test
     */
    public function buildSetsTypeConverterClassFromConfiguration()
    {
        $mockMappingConfiguration = $this->getMock(
            'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration',
            ['setTypeConverterOptions'], [], '', false
        );
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
        /** @var \PHPUnit_Framework_MockObject_MockObject $objectManager */
        $mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            ['get']);
        $this->subject->injectObjectManager($mockObjectManager);
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($mockMappingConfiguration));

        $mockMappingConfiguration->expects($this->once())
            ->method('setTypeConverterOptions')
            ->with($typeConverterClass, $defaultTypeConverterOptions);

        $this->subject->build($configuration);
    }

    /**
     * @test
     */
    public function buildSetsTypeConverterOptionsFromConfiguration()
    {
        $mockMappingConfiguration = $this->getMock(
            'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration',
            ['setTypeConverterOptions'], [], '', false
        );
        $typeConverterOptions = [
            PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => false,
            PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => true
        ];
        $configuration = [
            'typeConverter' => [
                'options' => $typeConverterOptions
            ]
        ];
        $defaultTypeConverterClass = 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\PersistentObjectConverter';
        /** @var \PHPUnit_Framework_MockObject_MockObject $objectManager */
        $mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            ['get']);
        $this->subject->injectObjectManager($mockObjectManager);
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($mockMappingConfiguration));

        $mockMappingConfiguration->expects($this->once())
            ->method('setTypeConverterOptions')
            ->with($defaultTypeConverterClass, $typeConverterOptions);

        $this->subject->build($configuration);
    }

    /**
     * @test
     */
    public function buildSetsSkipUnknownProperties()
    {
        $mockMappingConfiguration = $this->getMock(
            'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration',
            ['skipUnknownProperties'], [], '', false
        );
        $configuration = [];
        /** @var \PHPUnit_Framework_MockObject_MockObject $objectManager */
        $mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            ['get']);
        $this->subject->injectObjectManager($mockObjectManager);
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($mockMappingConfiguration));

        $mockMappingConfiguration->expects($this->once())
            ->method('skipUnknownProperties');

        $this->subject->build($configuration);
    }

    /**
     * @test
     */
    public function buildSetsAllowPropertiesFromConfiguration()
    {
        $mockMappingConfiguration = $this->getMock(
            'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration',
            ['allowProperties'], [], '', false
        );
        $configuration = [
            'allowProperties' => 'foo,bar'
        ];

        /** @var \PHPUnit_Framework_MockObject_MockObject $objectManager */
        $mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            ['get']);
        $this->subject->injectObjectManager($mockObjectManager);
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($mockMappingConfiguration));

        $mockMappingConfiguration->expects($this->once())
            ->method('allowProperties')
            ->with('foo', 'bar');

        $this->subject->build($configuration);
    }

    /**
     * @test
     */
    public function buildInitiallyDoesNotSetsAllowProperties()
    {
        $mockMappingConfiguration = $this->getMock(
            'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration',
            ['allowProperties'], [], '', false
        );
        $configuration = [];

        /** @var \PHPUnit_Framework_MockObject_MockObject $objectManager */
        $mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            ['get']);
        $this->subject->injectObjectManager($mockObjectManager);
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($mockMappingConfiguration));

        $mockMappingConfiguration->expects($this->never())
            ->method('allowProperties');

        $this->subject->build($configuration);
    }

    /**
     * @test
     * @covers ::getProperties
     */
    public function getPropertiesInitiallyReturnsEmptyArray()
    {
        $configuration = [];
        $expectedResult = [];
        $this->assertEquals(
            $expectedResult,
            $this->subject->_call('getProperties', $configuration)
        );
    }


    /**
     * @test
     * @covers ::getProperties
     */
    public function getPropertiesInitiallyReturnsPropertiesFromConfiguration()
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
            $this->subject->_call('getProperties', $configuration)
        );
    }

    /**
     * @test
     * @covers ::build
     */
    public function buildConfiguresAllowedProperties()
    {
        $subject = $this->getAccessibleMock(
            'CPSIT\\T3importExport\\Property\\PropertyMappingConfigurationBuilder',
            ['configure'], [], '', false);
        $mockMappingConfiguration = $this->getMock(
            'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration',
            ['allowProperties'], [], '', false
        );
        $configuration = [
            'allowProperties' => 'foo',
            'properties' => [
                'foo' => [

                ]
            ]
        ];

        /** @var \PHPUnit_Framework_MockObject_MockObject $objectManager */
        $mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            ['get']);
        $subject->injectObjectManager($mockObjectManager);
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($mockMappingConfiguration));
        $subject->expects($this->once())
            ->method('configure')
            ->with($configuration, $mockMappingConfiguration);

        $subject->build($configuration);
    }

    /**
     * @test
     */
    public function buildInitiallyDoesNotAllowAllProperties()
    {
        $mockMappingConfiguration = $this->getMock(
            'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration',
            ['allowAllProperties'], [], '', false
        );
        $configuration = [];

        /** @var \PHPUnit_Framework_MockObject_MockObject $objectManager */
        $mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            ['get']);
        $this->subject->injectObjectManager($mockObjectManager);
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($mockMappingConfiguration));

        $mockMappingConfiguration->expects($this->never())
            ->method('allowAllProperties');

        $this->subject->build($configuration);
    }

    /**
     * @test
     */
    public function buildInitiallySetsAllowAllProperties()
    {
        $mockMappingConfiguration = $this->getMock(
            'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration',
            ['allowAllProperties'], [], '', false
        );
        $configuration = [
            'allowAllProperties' => 1
        ];

        /** @var \PHPUnit_Framework_MockObject_MockObject $objectManager */
        $mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            ['get']);
        $this->subject->injectObjectManager($mockObjectManager);
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($mockMappingConfiguration));

        $mockMappingConfiguration->expects($this->once())
            ->method('allowAllProperties');

        $this->subject->build($configuration);
    }
}
