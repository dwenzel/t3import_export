<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace CPSIT\T3importExport\Tests\Unit\Component\Finisher;

use CPSIT\T3importExport\Component\Finisher\ValidateXML;
use CPSIT\T3importExport\Messaging\Message;
use CPSIT\T3importExport\Tests\Unit\Traits\MockMessageContainerTrait;
use CPSIT\T3importExport\Exception\MissingResourceException;
use CPSIT\T3importExport\Resource\XmlSchema;
use CPSIT\T3importExport\Validation\Configuration\ResourcePathConfigurationValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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

    protected const SCHEMA = <<<'XSD'
        <?xml version="1.0" encoding="utf-8"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
          <xs:element name="item"/>
        </xs:schema>
        XSD;

    protected const SCHEMA_WITH_INCLUDE = <<<'XSD'
        <?xml version="1.0" encoding="utf-8"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
          <xs:include schemaLocation="%s"/>
          <xs:element name="item" type="itemType"/>
        </xs:schema>
        XSD;

    protected const INCLUDED_SCHEMA = <<<'XSD'
        <?xml version="1.0" encoding="utf-8"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
          <xs:complexType name="itemType">
            <xs:sequence>
              <xs:element name="title" type="xs:string"/>
            </xs:sequence>
          </xs:complexType>
        </xs:schema>
        XSD;

    protected string $directory = '';

    protected function tearDown(): void
    {
        if ($this->directory !== '') {
            GeneralUtility::rmdir($this->directory, true);
        }
    }

    /**
     * Writes a file to the sub directory `schemas` of a temporary directory
     */
    protected function writeFile(string $name, string $content): string
    {
        if ($this->directory === '') {
            $this->directory = sys_get_temp_dir() . '/' . uniqid('t3import-export-xsd', true);
            GeneralUtility::mkdir_deep($this->directory . '/schemas');
        }
        $path = $this->directory . '/schemas/' . $name;
        file_put_contents($path, $content);

        return $path;
    }

    /**
     * Set up
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function setUp(): void
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
                1508774170,
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
        $schemaPath = $this->writeFile('schema.xsd', self::SCHEMA);
        $configuration = [
            'schema' => [
                'file' => 'mockSchema',
            ],
        ];
        $records = [];
        $result = [];

        $this->subject = $this->getMockBuilder(
            ValidateXML::class
        )
            ->onlyMethods(['loadResource', 'getAbsoluteFilePath', 'logNotice'])
            ->setConstructorArgs([$this->xmlReader, $this->pathValidator, $this->messageContainer])
            ->getMock();
        $this->subject->expects($this->once())
            ->method('loadResource')
            ->willReturn('<item/>');
        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with($configuration['schema']['file'])
            ->willReturn($schemaPath);

        $this->xmlReader->expects($this->once())
            ->method('setSchema')
            ->with(self::stringStartsWith('t3import-export-schema://'));

        $this->subject->process($configuration, $records, $result);
    }

    public static function validationDataProvider(): array
    {
        return [
            'valid' => ['<item><title>a</title></item>', 1_508_914_030],
            'invalid' => ['<item><name>a</name></item>', 1_508_776_068],
        ];
    }

    /**
     * @dataProvider validationDataProvider
     */
    public function testProcessUsesIncludedSchemaNextToTheSchema(string $xml, int $expectedNotice): void
    {
        $this->writeFile('types.xsd', self::INCLUDED_SCHEMA);
        $schemaPath = $this->writeFile('schema.xsd', sprintf(self::SCHEMA_WITH_INCLUDE, 'types.xsd'));
        $this->subject = $this->getMockBuilder(ValidateXML::class)
            ->onlyMethods(['loadResource', 'getAbsoluteFilePath', 'logNotice'])
            ->setConstructorArgs([new XMLReader(), $this->pathValidator, $this->messageContainer])
            ->getMock();
        $this->subject->method('loadResource')->willReturn($xml);
        $this->subject->method('getAbsoluteFilePath')->willReturn($schemaPath);
        $this->subject->expects($this->once())
            ->method('logNotice')
            ->with($expectedNotice);
        $records = [];
        $result = [];

        self::assertTrue($this->subject->process(['schema' => ['file' => 'schema.xsd']], $records, $result));
    }

    public static function includeNotAllowedDataProvider(): array
    {
        return [
            'URL' => ['https://example.org/types.xsd'],
            'outside of the directory of the schema' => ['../outside.xsd'],
            'other file type' => ['types.xml'],
        ];
    }

    /**
     * @dataProvider includeNotAllowedDataProvider
     */
    public function testProcessThrowsExceptionForIncludeThatIsNotAllowed(string $location): void
    {
        $this->writeFile('types.xml', self::INCLUDED_SCHEMA);
        $this->writeFile('../outside.xsd', self::INCLUDED_SCHEMA);
        $schemaPath = $this->writeFile('schema.xsd', sprintf(self::SCHEMA_WITH_INCLUDE, $location));
        $this->subject = $this->getMockBuilder(ValidateXML::class)
            ->onlyMethods(['loadResource', 'getAbsoluteFilePath'])
            ->setConstructorArgs([new XMLReader(), $this->pathValidator, $this->messageContainer])
            ->getMock();
        $this->subject->method('loadResource')->willReturn('<item/>');
        $this->subject->method('getAbsoluteFilePath')->willReturn($schemaPath);
        $records = [];
        $result = [];

        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(XmlSchema::ERROR_INCLUDE_NOT_ALLOWED);

        $this->subject->process(['schema' => ['file' => 'schema.xsd']], $records, $result);
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
                    1508776068,
                    ['was', 0, 'error'],
                    []
                ]
            );

        $this->subject->process($configuration, $records, $result);
    }
}
