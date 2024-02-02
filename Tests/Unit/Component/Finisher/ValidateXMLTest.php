<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace CPSIT\T3importExport\Tests\Unit\Component\Finisher;

use CPSIT\T3importExport\Component\Finisher\ValidateXML;
use CPSIT\T3importExport\Messaging\Message;
use CPSIT\T3importExport\Tests\Unit\Traits\MockMessageContainerTrait;
use CPSIT\T3importExport\Validation\Configuration\ResourcePathConfigurationValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use XMLReader;

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
    use MockMessageContainerTrait;

    /**
     * @var ValidateXML|MockObject
     */
    protected $subject;

    /**
     * @var ResourcePathConfigurationValidator|MockObject
     */
    protected $pathValidator;

    /**
     * @var XMLReader|MockObject
     */
    protected $xmlReader;

    /**
     * Set up
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function setUp(): void
    {
        $this->mockMessageContainer();
        $this->pathValidator = $this->getMockBuilder(ResourcePathConfigurationValidator::class)
            ->setMethods(['isValid'])->getMock();
        $this->xmlReader = $this->getMockBuilder(XMLReader::class)
            ->setMethods(
                [
                    'setParserProperty',
                    'isValid',
                    'setSchema',
                    'read',
                    'close'
                ])
            ->getMock();
        $this->subject = new ValidateXML($this->xmlReader, $this->pathValidator, $this->messageContainer);
    }

    public function testGetNoticeCodesReturnsMemberConstant(): void
    {
        $this->assertSame(
            ValidateXML::NOTICE_CODES,
            $this->subject->getNoticeCodes()
        );
    }

    public function testGetErrorCodesReturnsMemberConstant(): void
    {
        $this->assertSame(
            ValidateXML::ERROR_CODES,
            $this->subject->getErrorCodes()
        );
    }

    /**
     * Invalid configuration data provider
     * @return array
     */
    public function invalidConfigurationDataProvider(): array
    {
        return [
            'schema file: must not be array' => [
                [
                    'target' => [
                        'file' => 'foo',
                        'schema' => []
                    ]
                ],
                1_508_774_170,
                ['array']
            ]
        ];
    }

    /**
     * @dataProvider invalidConfigurationDataProvider
     * @param array $configuration
     * @param int $error
     * @param array $arguments
     */
    public function testIsConfigurationForInvalidConfigurationReturnsFalse(array $configuration, int $error, array $arguments): void
    {
        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $expectedTitle = 'Invalid type for target schema';
        $expectedDescription = "config['target']['schema'] must be a string, array given.\nMessage ID 1508774170 in component CPSIT\T3importExport\Component\Finisher\ValidateXML";
        $this->pathValidator->expects($this->once())->method('isValid')
            ->willReturn(true);
        $this->messageContainer->expects($this->once())
            ->method('addMessage');

        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    public function testIsConfigurationReturnsFalseForInvalidPathConfiguration(): void
    {
        $configuration = ['foo'];
        $this->pathValidator->expects($this->once())->method('isValid')
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
    public function validConfigurationDataProvider(): array
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
     * @dataProvider validConfigurationDataProvider
     */
    public function testIsConfigurationValidReturnsTrueForValidConfiguration($configuration): void
    {
        $this->pathValidator->expects($this->once())->method('isValid')
            ->willReturn(true);

        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    public function testProcessReturnsFalseIfResourceCanNotBeLoaded(): void
    {
        $result = [];
        $records = [];

        $this->assertFalse(
            $this->subject->process([], $records, $result)
        );
    }

    public function testProcessValidatesContent(): void
    {
        $configuration = [];
        $records = [];
        $result = [];

        $validXML = 'foo';
        $this->subject = $this->getMockBuilder(ValidateXML::class)
            ->setMethods(['loadResource', 'logNotice'])
            ->setConstructorArgs([$this->xmlReader, $this->pathValidator, $this->messageContainer])
            ->getMock();
        $this->subject->expects($this->once())
            ->method('loadResource')
            ->willReturn($validXML);
        $this->xmlReader->expects($this->once())
            ->method('setParserProperty')
            ->with(...[XMLReader::VALIDATE, true]);
        $this->xmlReader->expects($this->once())
            ->method('isValid');

        $this->subject->process($configuration, $records, $result);
    }

    public function testProcessUsesSchemaFromFile(): void
    {
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
            ->setMethods(['loadResource', 'getAbsoluteFilePath', 'logNotice'])
            ->setConstructorArgs([$this->xmlReader, $this->pathValidator, $this->messageContainer])
            ->getMock();
        $this->subject->expects($this->once())
            ->method('loadResource')
            ->willReturn($validXML);
        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with($configuration['schema']['file'])
            ->willReturn($schemaPath);

        $this->xmlReader->expects($this->once())
            ->method('setSchema')
            ->with(...[$schemaPath]);

        $this->subject->process($configuration, $records, $result);
    }

    public function testProcessLogsNoticeIfValidationOfXMLFails(): void
    {
        $configuration = [];
        $records = [];
        $result = [];

        $validXML = 'bar';
        $this->subject = $this->getMockBuilder(ValidateXML::class)
            ->setMethods(['loadResource', 'logNotice'])
            ->setConstructorArgs([$this->xmlReader, $this->pathValidator, $this->messageContainer])
            ->getMock();
        $this->subject->expects($this->once())
            ->method('loadResource')
            ->willReturn($validXML);
        $this->xmlReader->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->subject->expects($this->once())
            ->method('logNotice')
            ->with(...[
                    1_508_776_068,
                    ['was', 0, 'error'],
                    []
                ]
            );

        $this->subject->process($configuration, $records, $result);
    }
}
