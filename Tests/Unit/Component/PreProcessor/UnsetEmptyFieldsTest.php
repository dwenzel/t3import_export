<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

/**
 * This file is part of the "Import Export" project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use CPSIT\T3importExport\Component\PreProcessor\UnsetEmptyFields;
use PHPUnit\Framework\TestCase;

/**
 * Class UnsetEmptyFieldsTest
 */
class UnsetEmptyFieldsTest extends TestCase
{
    /**
     * @var UnsetEmptyFields|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * set up the subject
     */
    public function setUp(): void
    {
        $this->subject = $this->getMockBuilder(\CPSIT\T3importExport\Component\PreProcessor\UnsetEmptyFields::class)
            ->setMethods(['dummy'])->getMock();
    }

    /**
     * Data provider for configuration validation test
     */
    public function configurationDataProvider()
    {
        return [
            // empty configuration is invalid
            [[], false],
            [['foo'], false],
            [['fields' => ''], false],
            [['fields' => 'foo,bar'], true],
        ];
    }

    /**
     * @test
     * @dataProvider configurationDataProvider
     */
    public function isConfigurationValidReturnsCorrectResult($configuration, $expectedResult)
    {
        $this->assertSame(
            $expectedResult,
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * Data provider for processing test
     */
    public function processDataProvider()
    {
        return [
            [
                ['fields' => 'foo'], // configuration
                [], // incomingRecord
                [] // expectedResult
            ],
            [
                ['fields' => 'foo'],
                ['foo' => ''],
                []
            ],
            [
                ['fields' => 'foo'],
                ['foo' => 0],
                []
            ],
            [
                ['fields' => 'foo'],
                ['foo' => 0.0],
                []
            ],
            [
                ['fields' => 'foo'],
                ['foo' => "0"],
                []
            ],
            [
                ['fields' => 'foo'],
                ['foo' => null],
                []
            ],
            [
                ['fields' => 'foo'],
                ['foo' => false],
                []
            ],
            [
                ['fields' => 'foo'],
                ['foo' => []],
                []
            ],
            [
                ['fields' => 'foo'],
                ['foo' => 'bar'],
                ['foo' => 'bar']
            ],
            [
                ['fields' => 'foo'],
                ['bar' => ''],
                ['bar' => '']
            ],
        ];
    }

    /**
     * @test
     * @dataProvider processDataProvider
     *
     * @param array $configuration
     * @param array $incomingRecord
     * @param array $expectedResult
     */
    public function processUnsetsFieldsCorrectly($configuration, $incomingRecord, $expectedResult)
    {
        $this->subject->process($configuration, $incomingRecord);

        $this->assertSame(
            $expectedResult,
            $incomingRecord
        );
    }
}
