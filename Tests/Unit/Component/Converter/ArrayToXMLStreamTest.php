<?php /** @noinspection PhpUnitTestsInspection */

namespace CPSIT\T3importExport\Tests\Unit\Component\Converter;

use CPSIT\T3importExport\Component\Converter\ArrayToXMLStream;
use CPSIT\T3importExport\Domain\Model\DataStream;
use CPSIT\T3importExport\Property\PropertyMappingConfigurationBuilder;
use CPSIT\T3importExport\Validation\Configuration\MappingConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TargetClassConfigurationValidator;
use PHPUnit\Framework\Attributes\Test;
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
     *
     */
    protected function setUp(): void
    {
        $this->subject = new ArrayToXMLStream();
    }

    public function testGetMappingConfiguration(): void
    {
        $this->markTestSkipped('not sure what is supposed to be tested here');
        // test for default configurator
        $propertyMappingConfiguration = $this->getMockBuilder(PropertyMappingConfiguration::class)
            ->onlyMethods(['setTypeConverterOptions', 'skipUnknownProperties'])
            ->getMock();
        $propertyMappingConfiguration->expects($this->once())
            ->method('setTypeConverterOptions')
            ->willReturn($propertyMappingConfiguration);
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

    #[Test]
    public function isConfigurationValidValidatesTargetClass(): void
    {
        /** @var TargetClassConfigurationValidator|MockObject $mockedTargetValidator */
        $mockedTargetValidator = $this->getMockBuilder(TargetClassConfigurationValidator::class)
            ->onlyMethods(['isValid'])
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

    #[Test]
    public function isConfigurationValidValidatesMappingClass(): void
    {
        /** @var TargetClassConfigurationValidator|MockObject $mockedTargetValidator */
        $mockedTargetValidator = $this->getMockBuilder(TargetClassConfigurationValidator::class)
            ->onlyMethods(['isValid'])
            ->getMock();
        $this->subject->injectTargetClassConfigurationValidator($mockedTargetValidator);

        /** @var MappingConfigurationValidator|MockObject $mockedMappingValidator */
        $mockedMappingValidator = $this->getMockBuilder(
            MappingConfigurationValidator::class)->onlyMethods(['isValid'])
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

    #[Test]
    public function defaultConfigurationRootEnclosure(): void
    {
        $testArray = ['value' => 'a'];
        $testConfig = [
            'targetClass' => DataStream::class
        ];
        $resultObject = new DataStream();
        $result = $this->subject->convert($testArray, $testConfig);
        $this->assertInstanceOf(DataStream::class, $result);
        $this->assertEquals('<row><value>a</value></row>', $result->getStreamBuffer());
    }

    #[Test]
    public function customConfigurationRootEnclosure(): void
    {
        $testArray = ['value' => 'a'];
        $testConfig = [
            'targetClass' => DataStream::class,
            'nodeName' => 'unitTest'
        ];
        /** @var DataStream $result */
        $result = $this->subject->convert($testArray, $testConfig);
        $this->assertInstanceOf(DataStream::class, $result);
        $expected = '<unitTest><value>a</value></unitTest>';
        $this->assertEquals($expected, $result->getStreamBuffer());
    }

    #[Test]
    public function mappingXMLGeneration(): void
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
        /** @var DataStream $result */
        $result = $this->subject->convert($testArray, $testConfig);
        $this->assertInstanceOf(DataStream::class, $result);
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

    #[Test]
    public function attributeXMLGeneration(): void
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
        $result = $this->subject->convert($testArray, $testConfig);
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
    protected function injectPropertyMapperObject(): MockObject|PropertyMapper
    {
        /** @var PropertyMapper|MockObject $mockPropertyMapper */
        $mockPropertyMapper = $this->getMockBuilder(PropertyMapper::class)
            ->disableOriginalConstructor()
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
