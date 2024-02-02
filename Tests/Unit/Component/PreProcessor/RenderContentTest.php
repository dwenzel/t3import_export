<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

use CPSIT\T3importExport\Component\PreProcessor\RenderContent;
use CPSIT\T3importExport\Tests\Unit\Traits\MockContentObjectRendererTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockTypoScriptFrontendControllerTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockTypoScriptServiceTrait;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;

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
 * Class RenderContentTest
 *
 * @package CPSIT\T3importExport\Tests\Service\PreProcessor
 * @coversDefaultClass \CPSIT\T3importExport\Component\PreProcessor\RenderContent
 */
class RenderContentTest extends TestCase
{
    use MockContentObjectRendererTrait,
        MockTypoScriptFrontendControllerTrait,
        MockTypoScriptServiceTrait;

    protected RenderContent $subject;

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    protected function setUp(): void
    {
        $this->mockTypoScriptService();
        $this->mockTypoScriptFrontendController();
        $this->mockContentObjectRenderer();
        $this->subject = new RenderContent($this->contentObjectRenderer, $this->typoScriptService);
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsInitiallyFalse(): void
    {
        $mockConfiguration = ['foo'];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfFieldsIsNotArray(): void
    {
        $config = [
            'fields' => 'foo'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfFieldValueIsNotString(): void
    {
        $config = [
            'fields' => [
                'foo' => 0
            ]
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfFieldValueIsEmpty(): void
    {
        $config = [
            'fields' => [
                'foo' => ''
            ]
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        $config = [
            'fields' => [
                'foo' => ['bar'],
                'baz' => ['fooBar']
            ]
        ];
        $this->assertTrue(
            $this->subject->isConfigurationValid($config)
        );
    }

    public function testProcessRendersContent(): void
    {
        $fieldName = 'fooField';
        $renderObjectType = 'TEXT';
        $record = [];
        $configuration = [
            'fields' => [
                $fieldName => [
                    '_typoScriptNodeValue' => $renderObjectType,
                    'value' => '1'
                ]
            ]
        ];
        $convertedConfiguration = ['boo'];
        $expectedConfiguration = $configuration['fields'][$fieldName];
        $expectedContent =  'bar';

        $this->typoScriptService->expects($this->once())
            ->method('convertPlainArrayToTypoScriptArray')
            ->with(...[$expectedConfiguration])
            ->willReturn($convertedConfiguration);

        $this->contentObjectRenderer->expects($this->once())
            ->method('getContentObject')
            ->with(...[$renderObjectType])
            ->willReturn($this->contentObject);

        $this->contentObject->expects($this->once())
            ->method('render')
            ->with(...[$convertedConfiguration])
            ->willReturn($expectedContent);

        $this->subject->process($configuration, $record);
        $this->assertSame(
            $expectedContent,
            $record[$fieldName]
        );
    }

    /**
     * @throws ContentRenderingException
     */
    public function testProcessRendersContentForMultipleRowFields(): void
    {
        $record = [
            'fooField' => [
                [
                    'barField' => 'initialValue'
                ]
            ]
        ];
        $configuration = [
            'fields' => [
                'fooField' => [
                    'multipleRows' => '1',
                    'fields' => [
                        'barField' => [
                            '_typoScriptNodeValue' => 'TEXT',
                            'value' => '1'
                        ]
                    ]
                ],
            ]
        ];

        $typoScriptConf = ['foo'];

        $this->typoScriptService->expects($this->once())
            ->method('convertPlainArrayToTypoScriptArray')
            ->willReturn($typoScriptConf);

        $this->subject->renderContent($configuration, $record);
    }
}
