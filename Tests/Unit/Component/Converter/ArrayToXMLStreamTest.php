<?php /** @noinspection PhpUnitTestsInspection */

namespace CPSIT\T3importExport\Tests\Unit\Component\Converter;

use CPSIT\T3importExport\Component\Converter\ArrayToXMLStream;
use CPSIT\T3importExport\Domain\Model\DataStream;
use CPSIT\T3importExport\Property\PropertyMappingConfigurationBuilder;
use CPSIT\T3importExport\Validation\Configuration\MappingConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TargetClassConfigurationValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
class ArrayToXMLStreamTest extends TestCase
{

    /**
     * @var MockObject|ArrayToXMLStream
     */
    protected $subject;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->markTestIncomplete('DI of class must be adapted');
        $this->mockObjectManager();
        $this->subject = new ArrayToXMLStream();
    }

    /**
     * @return MockObject|ObjectManager
     */
    protected function mockObjectManager()
    {
        $mockObjectManager = null;
        /** @var ObjectManager|MockObject $mockObjectManager */
        $this->objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $mockObjectManager;
    }

    public function testGetMappingConfiguration(): void
    {
        // test for default configurator
        $propertyMappingConfiguration = $this->getMockBuilder(PropertyMappingConfiguration::class)
            ->setMethods(['setTypeConverterOptions', 'skipUnknownProperties'])
            ->getMock();
        $propertyMappingConfiguration->expects($this->once())
            ->method('setTypeConverterOptions')
            ->will($this->returnValue($propertyMappingConfiguration));

        $this->objectManager->expects($this->once())
            ->method('get')
            ->with(...[PropertyMappingConfiguration::class])
            ->will($this->returnValue($propertyMappingConfiguration));

        $configurator = $this->subject->getMappingConfiguration();

        $this->assertSame(
            $propertyMappingConfiguration,
            $configurator
        );

        // test storage
        $configurator = $this->subject->getMappingConfiguration();

        $this->assertSame(
            $propertyMappingConfiguration,
            $configurator
        );

    }

    /**
     * @test
     */
    public function isConfigurationValidValidatesTargetClass()
    {
        /** @var TargetClassConfigurationValidator|MockObject $mockedTargetValidator */
        $mockedTargetValidator = $this->getMockBuilder(TargetClassConfigurationValidator::class)
            ->setMethods(['isValid'])
            ->getMock();
        $this->subject->injectTargetClassConfigurationValidator($mockedTargetValidator);

        /** @var MappingConfigurationValidator|MockObject $mockedMappingValidator */
        $mockedMappingValidator = $this->getMockBuilder(MappingConfigurationValidator::class)
            ->getMock();
        $this->subject->injectMappingConfigurationValidator($mockedMappingValidator);


        $config = [
            'targetClass' => DataStream::class
        ];

        $mockedTargetValidator->expects($this->once())
            ->method('isValid')
            ->with($config);
        $this->subject->isConfigurationValid($config);
    }

    /**
     * @test
     */
    public function isConfigurationValidValidatesMappingClass()
    {
        /** @var TargetClassConfigurationValidator|MockObject $mockedTargetValidator */
        $mockedTargetValidator = $this->getMockBuilder(TargetClassConfigurationValidator::class)
            ->setMethods(['isValid'])
            ->getMock();
        $this->subject->injectTargetClassConfigurationValidator($mockedTargetValidator);


        /** @var MappingConfigurationValidator|MockObject $mockedMappingValidator */
        $mockedMappingValidator = $this->getMockBuilder(
            MappingConfigurationValidator::class)->setMethods(['isValid'])
            ->getMock();
        $this->subject->injectMappingConfigurationValidator($mockedMappingValidator);


        $config = [
            'targetClass' => DataStream::class
        ];

        $mockedTargetValidator->expects($this->once())
            ->method('isValid')
            ->with($config)
            ->willReturn(true);
        $mockedMappingValidator->expects($this->once())
            ->method('isValid')
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

        $objectManager = $this->mockObjectManager();
        $objectManager->expects($this->once())
            ->method('get')
            ->with(...[DataStream::class])
            ->willReturn($resultObject);

        /** @var DataStream $result */
        $result = $this->subject->convert($testArray, $testConfig);
        $this->assertSame($resultObject, $result);
        $this->assertEquals($result->getStreamBuffer(), '<row><value>a</value></row>');
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

        $objectManager = $this->mockObjectManager();
        $objectManager->expects($this->once())
            ->method('get')
            ->with(DataStream::class)
            ->willReturn($resultObject);

        /** @var DataStream $result */
        $result = $this->subject->convert($testArray, $testConfig);
        $this->assertSame($resultObject, $result);
        $expected = '<unitTest><value>a</value></unitTest>';
        $this->assertEquals($expected, $result->getStreamBuffer());
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

        $objectManager = $this->mockObjectManager();
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
        $this->assertEquals($expected, $result->getStreamBuffer());
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

        $objectManager = $this->mockObjectManager();
        $objectManager->expects($this->once())
            ->method('get')
            ->with(...[DataStream::class])
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
        $this->assertEquals($expected, $result->getStreamBuffer());
    }

    /**
     * @return MockObject|PropertyMapper
     */
    protected function injectPropertyMapperObject()
    {
        /** @var PropertyMapper|MockObject $mockPropertyMapper */
        $mockPropertyMapper = $this->getMockBuilder(PropertyMapper::class)
            ->getMock();

        $this->subject->injectPropertyMapper($mockPropertyMapper);

        return $mockPropertyMapper;
    }

    /**
     * @return PropertyMappingConfigurationBuilder|MockObject
     */
    protected function injectPropertyMappingConfigurationBuilderObject()
    {


        /** @var PropertyMappingConfigurationBuilder|MockObject $mockPropertyMappingBuilder */
        $mockPropertyMappingBuilder = $this->getMockBuilder(PropertyMappingConfigurationBuilder::class)
            ->getMock();

        $this->subject->injectPropertyMappingConfigurationBuilder($mockPropertyMappingBuilder);

        return $mockPropertyMappingBuilder;
    }

    /**
     * @return TargetClassConfigurationValidator|MockObject
     */
    protected function injectTargetClassConfigurationValidatorObject()
    {
        /** @var TargetClassConfigurationValidator|MockObject $targetClassConfigurationValidator */
        $targetClassConfigurationValidator = $this->getMockBuilder(TargetClassConfigurationValidator::class)
            ->getMock();

        $this->subject->injectTargetClassConfigurationValidator($targetClassConfigurationValidator);

        return $targetClassConfigurationValidator;
    }

    protected function injectMappingConfigurationValidatorObject()
    {
        /** @var MappingConfigurationValidator $configurationValidator */
        $configurationValidator = $this->getMockBuilder(MappingConfigurationValidator::class)
            ->getMock();
        $this->subject->injectMappingConfigurationValidator($configurationValidator);

        return $configurationValidator;
    }
}
