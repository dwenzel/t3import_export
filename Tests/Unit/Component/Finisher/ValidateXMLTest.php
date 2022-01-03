<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\Finisher;

use CPSIT\T3importExport\Component\Finisher\ValidateXML;
use CPSIT\T3importExport\Domain\Model\Dto\FileInfo;
use CPSIT\T3importExport\Domain\Model\TaskResult;
use CPSIT\T3importExport\Validation\Configuration\ResourcePathConfigurationValidator;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/***************************************************************
 *  Copyright notice
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
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
 * Class ValidateXMLTest
 */
class ValidateXMLTest extends TestCase
{
    /**
     * @var ValidateXML|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * @var ResourcePathConfigurationValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $pathValidator;

    /**
     * @var \XMLReader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $xmlReader;

    /**
     * Set up
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            ValidateXML::class, ['dummy', 'logError', 'logNotice']
        );
        $this->pathValidator = $this->getMockBuilder(ResourcePathConfigurationValidator::class)
            ->setMethods(['validate'])->getMock();
        $this->subject->injectResourcePathConfigurationValidator(
            $this->pathValidator
        );
        $this->xmlReader = $this->getMockBuilder(\XMLReader::class)
            ->setMethods(
                [
                    'XML',
                    'setParserProperty',
                    'isValid',
                    'setSchema',
                    'read',
                    'close'
                ])
            ->getMock();
        $this->subject->injectXMLReader($this->xmlReader);
    }

    /**
     * @test
     */
    public function xmlReaderCanBeInjected() {
        /** @var \XMLReader|\PHPUnit_Framework_MockObject_MockObject $xmlReader */
        $xmlReader = $this->getMockBuilder(\XMLReader::class)
            ->getMock();
        $this->subject->injectXMLReader($xmlReader);

        $this->assertAttributeSame(
            $xmlReader,
            'xmlReader',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getNoticeCodesReturnsMemberConstant() {
        $this->assertSame(
            ValidateXML::NOTICE_CODES,
            $this->subject->getNoticeCodes()
        );
    }

    /**
     * @test
     */
    public function getErrorCodesReturnsMemberConstant() {
        $this->assertSame(
            ValidateXML::ERROR_CODES,
            $this->subject->getErrorCodes()
        );
    }

    /**
     * Invalid configuration data provider
     * @return array
     */
    public function invalidConfigurationDataProvider()
    {
        return [
            'schema file: must not be array' => [
                [
                    'target' => [
                        'file' => 'foo',
                        'schema' => []
                    ]
                ],
                1508774170,
                ['array']
            ]
        ];
    }

    /**
     * @test
     * @param array $configuration
     * @param int $error
     * @param array $arguments
     * @dataProvider invalidConfigurationDataProvider
     */
    public function isConfigurationForInvalidConfigurationReturnsFalse($configuration, $error, $arguments)
    {
        $this->pathValidator->expects($this->once())->method('validate')
            ->willReturn(true);
        $this->subject->expects($this->once())
            ->method('logError')
            ->with($error, $arguments);

        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationReturnsFalseForInvalidPathConfiguration()
    {
        $configuration = ['foo'];
        $this->pathValidator->expects($this->once())->method('validate')
            ->with($configuration)
            ->willReturn(false);

        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * Valid configuration data provider
     * @return array
     */
    public function validConfigurationDataProvider()
    {
        return [
            // file configuration omitted - is handled by
            'schema is string' => [
                [
                    'schema' => 'http://typo3.org',

                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validConfigurationDataProvider
     */
    public function isConfigurationValidReturnsTrueForValidConfiguration($configuration)
    {
        $this->pathValidator->expects($this->once())->method('validate')
            ->willReturn(true);

        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function processReturnsFalseIfResourceCanNotBeLoaded()
    {
        $result = [];
        $records = [];

        $this->assertFalse(
            $this->subject->process([], $records, $result)
        );
    }


    /**
     * @test
     */
    public function processValidatesContent() {
        $configuration = [];
        $records = [];
        $result = [];

        $validXML = 'foo';
        $this->subject = $this->getAccessibleMock(
            ValidateXML::class, ['loadResource', 'logNotice']
        );
        $this->subject->injectXMLReader($this->xmlReader);
        $this->subject->expects($this->once())
            ->method('loadResource')
            ->willReturn($validXML);
        $this->xmlReader->expects($this->once())
            ->method('XML')
            ->with($validXML);
        $this->xmlReader->expects($this->once())
            ->method('setParserProperty')
            ->with(\XMLReader::VALIDATE, true);
        $this->xmlReader->expects($this->once())
            ->method('isValid');

        $this->subject->process($configuration, $records, $result);
    }

    /**
     * @test
     */
    public function processUsesSchemaFromFile() {
        $schemaPath = 'foo';
        $validXML = 'bar';
        $configuration = [
            'schema' => [
                'file' => 'mockSchema'
            ]
        ];
        $records = [];
        $result = [];

        $this->subject = $this->getMockBuilder(
            ValidateXML::class)
            ->setMethods(['loadResource', 'getAbsoluteFilePath', 'logNotice']
            )->getMock();
        $this->subject->injectXMLReader($this->xmlReader);
        $this->subject->expects($this->once())
            ->method('loadResource')
            ->willReturn($validXML);
        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with($configuration['schema']['file'])
            ->willReturn($schemaPath);

        $this->xmlReader->expects($this->once())
            ->method('setSchema')
            ->with($schemaPath);

        $this->subject->process($configuration, $records, $result);
    }

    /**
     * @test
     */
    public function processLogsNoticeIfValidationOfXMLFails() {
        $configuration = [];
        $records = [];
        $result = [];

        $validXML = 'bar';
        $this->subject = $this->getMockBuilder(ValidateXML::class)
            ->setMethods(['loadResource', 'logNotice'])
            ->getMock();
        $this->subject->injectXMLReader($this->xmlReader);
        $this->subject->expects($this->once())
            ->method('loadResource')
            ->willReturn($validXML);
        $this->xmlReader->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->subject->expects($this->once())
            ->method('logNotice')
            ->with(
                1508776068,
                ['was', 0, 'error'],
                []
            );

        $this->subject->process($configuration, $records, $result);
    }
}
