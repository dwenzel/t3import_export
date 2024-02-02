<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

use CPSIT\T3importExport\Component\PreProcessor\ConcatenateFields;
use CPSIT\T3importExport\Tests\Unit\Traits\MockContentObjectRendererTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockTypoScriptFrontendControllerTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

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
 * Class ConcatenateFieldsTest
 *
 * @package CPSIT\T3importExport\Tests\Service\PreProcessor
 * @coversDefaultClass \CPSIT\T3importExport\Component\PreProcessor\ConcatenateFields
 */
class ConcatenateFieldsTest extends TestCase
{
    use MockTypoScriptFrontendControllerTrait,
        MockContentObjectRendererTrait;

    protected ConcatenateFields $subject;

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    protected function setUp(): void
    {
        $this->mockTypoScriptFrontendController();
        $this->mockContentObjectRenderer();
        $this->subject = new ConcatenateFields($this->contentObjectRenderer);
    }

    /**
     * @test
     * @covers ::process
     */
    public function processConcatenatesFieldValues(): void
    {
        $mockRecord = [
            'fooField' => 'foo',
            'barField' => 'bar',
            'baz' => ''
        ];
        $configuration = [
            'targetField' => 'baz',
            'fields' => [
                'fooField' => [],
                'barField' => []
            ]
        ];
        $expectedResult = [
            'fooField' => 'foo',
            'barField' => 'bar',
            'baz' => 'foobar'
        ];
        $this->subject->process($configuration, $mockRecord);
        $this->assertSame($expectedResult, $mockRecord);
    }

    /**
     * @test
     * @covers ::process
     */
    public function processWrapsFieldValues(): void
    {
        $originalFieldValue = 'foo';
        $wrapConfiguration = 'baz-|-boom';
        $wrappedFieldValue = 'baz-foo-boom';

        $mockRecord = [
            'fooField' => $originalFieldValue
        ];
        $configuration = [
            'targetField' => 'baz',
            'fields' => [
                'fooField' => [
                    'wrap' => $wrapConfiguration
                ],
            ]
        ];
        $this->contentObjectRenderer->expects($this->once())
            ->method('wrap')
            ->with(...[$originalFieldValue, $wrapConfiguration])
            ->willReturn($wrappedFieldValue);

        $expectedResult = [
            'fooField' => $wrappedFieldValue,
            'baz' => $wrappedFieldValue
        ];
        $this->subject->process($configuration, $mockRecord);
        $this->assertSame($expectedResult, $mockRecord);
    }

    /**
     * @test
     * @covers ::process
     */
    public function processNoTrimWrapsFieldValues(): void
    {
        $originalFieldValue = 'foo';
        $wrapConfiguration = 'baz- | -boom';
        $wrappedFieldValue = 'baz-foo-boom';

        $mockRecord = [
            'fooField' => $originalFieldValue
        ];
        $configuration = [
            'targetField' => 'baz',
            'fields' => [
                'fooField' => [
                    'noTrimWrap' => $wrapConfiguration
                ],
            ]
        ];
        $this->contentObjectRenderer->expects($this->once())
            ->method('noTrimWrap')
            ->with(...[$originalFieldValue, $wrapConfiguration])
            ->willReturn($wrappedFieldValue);

        $expectedResult = [
            'fooField' => $wrappedFieldValue,
            'baz' => $wrappedFieldValue
        ];
        $this->subject->process($configuration, $mockRecord);
        $this->assertSame($expectedResult, $mockRecord);
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfTargetFieldIsNotSet(): void
    {
        $mockConfiguration = ['foo'];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfTargetFieldIsNotString(): void
    {
        $mockConfiguration = [
            'targetField' => 1,
            'fields' => []
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfFieldsIsNotSet(): void
    {
        $mockConfiguration = [
            'targetField' => 'foo'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfFieldsIsNotArray(): void
    {
        $mockConfiguration = [
            'targetField' => 'foo',
            'fields' => 'invalidStringValue'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        $validConfiguration = [
            'targetField' => 'foo',
            'fields' => [
                'foo' => []
            ]
        ];
        $this->assertTrue(
            $this->subject->isConfigurationValid($validConfiguration)
        );
    }
}
