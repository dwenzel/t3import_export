<?php

declare(strict_types=1);
/** @noinspection PhpComposerExtensionStubsInspection */

namespace CPSIT\T3importExport\Tests\Unit\Component\Finisher;

use CPSIT\T3importExport\Component\Finisher\ValidateXML;
use CPSIT\ImportExportCore\Messaging\Message;
use CPSIT\ImportExportCore\Messaging\MessageContainer;
use CPSIT\ImportExportCore\Exception\MissingResourceException;
use CPSIT\ImportExportCore\Resource\HttpResourceLoader;
use CPSIT\T3importExport\Resource\AllowedHosts;
use CPSIT\T3importExport\Resource\ResourceAccess;
use CPSIT\T3importExport\Resource\Typo3PathResolver;
use CPSIT\T3importExport\Resource\XmlSchema;
use CPSIT\T3importExport\Validation\Configuration\ResourcePathConfigurationValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use TYPO3\CMS\Core\Core\Environment;
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
    /**
     * @var ValidateXML|MockObject
     */
    protected $subject;

    /**
     * @var ResourcePathConfigurationValidator|MockObject
     */
    protected $pathValidator;

    /**
     * @var \XMLReader|MockObject
     */
    protected $xmlReader;

    /**
     * @var MessageContainer|MockObject
     */
    protected $messageContainer;

    protected const string SCHEMA = <<<'XSD'
        <?xml version="1.0" encoding="utf-8"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
          <xs:element name="item"/>
        </xs:schema>
        XSD;

    protected const string SCHEMA_WITH_INCLUDE = <<<'XSD'
        <?xml version="1.0" encoding="utf-8"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
          <xs:include schemaLocation="%s"/>
          <xs:element name="item" type="itemType"/>
        </xs:schema>
        XSD;

    protected const string INCLUDED_SCHEMA = <<<'XSD'
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

    protected function writeFile(string $name, string $content): string
    {
        if ($this->directory === '') {
            $this->directory = Environment::getVarPath() . '/import/tests/' . uniqid('finisher', true);
            GeneralUtility::mkdir_deep($this->directory);
        }
        $path = $this->directory . '/' . $name;
        file_put_contents($path, $content);

        return $path;
    }

    protected function createResourceAccess(): ResourceAccess
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('sendRequest');

        return new ResourceAccess(
            new Typo3PathResolver(directories: [Environment::getVarPath() . '/import/tests']),
            new HttpResourceLoader($client, $this->createStub(RequestFactoryInterface::class), ['data.example.org']),
            new AllowedHosts(['data.example.org'])
        );
    }

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->messageContainer = $this->createMock(MessageContainer::class);

        $this->pathValidator = $this->getMockBuilder(ResourcePathConfigurationValidator::class)
            ->onlyMethods(['isValid'])->getMock();

        $this->xmlReader = $this->getMockBuilder(\XMLReader::class)
            ->onlyMethods(
                [
                    'setParserProperty',
                    'isValid',
                    'setSchema',
                    'read',
                    'close',
                ]
            )
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
     */
    public static function invalidConfigurationDataProvider(): array
    {
        return [
            'schema file: must not be array' => [
                [
                    'target' => [
                        'file' => 'foo',
                        'schema' => [],
                    ],
                ],
                1_508_774_170,
                ['array'],
            ],
        ];
    }

    #[DataProvider('invalidConfigurationDataProvider')]
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
     */
    public static function validConfigurationDataProvider(): array
    {
        return [
            // file configuration omitted - is handled by
            'schema is string' => [
                [
                    'schema' => 'http://typo3.org',

                ],
            ],
        ];
    }

    #[DataProvider('validConfigurationDataProvider')]
    public function testIsConfigurationValidReturnsTrueForValidConfiguration(array $configuration): void
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
            ->onlyMethods(['loadResource', 'logNotice'])
            ->setConstructorArgs([$this->xmlReader, $this->pathValidator, $this->messageContainer])
            ->getMock();
        $this->subject->expects($this->once())
            ->method('loadResource')
            ->willReturn($validXML);
        $this->xmlReader->expects($this->once())
            ->method('setParserProperty')
            ->with(...[\XMLReader::VALIDATE, true]);
        $this->xmlReader->expects($this->once())
            ->method('isValid');

        $this->subject->process($configuration, $records, $result);
    }

    public function testProcessUsesSchemaFromFile(): void
    {
        $schemaFile = $this->writeFile('schema.xsd', self::SCHEMA);
        $configuration = [
            'schema' => [
                'file' => $schemaFile,
            ],
        ];
        $records = [];
        $result = [];

        $this->subject = $this->getMockBuilder(
            ValidateXML::class
        )
            ->onlyMethods(['loadResource', 'logNotice'])
            ->setConstructorArgs([$this->xmlReader, $this->pathValidator, $this->messageContainer])
            ->getMock();
        $this->subject->setResourceAccess($this->createResourceAccess());
        $this->subject->expects($this->once())
            ->method('loadResource')
            ->willReturn('<item/>');

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

    #[DataProvider('validationDataProvider')]
    public function testProcessUsesIncludedSchemaNextToTheSchema(string $xml, int $expectedNotice): void
    {
        $this->writeFile('types.xsd', self::INCLUDED_SCHEMA);
        $schemaFile = $this->writeFile('schema.xsd', sprintf(self::SCHEMA_WITH_INCLUDE, 'types.xsd'));
        $this->subject = $this->getMockBuilder(ValidateXML::class)
            ->onlyMethods(['loadResource', 'logNotice'])
            ->setConstructorArgs([new XMLReader(), $this->pathValidator, $this->messageContainer])
            ->getMock();
        $this->subject->setResourceAccess($this->createResourceAccess());
        $this->subject->method('loadResource')->willReturn($xml);
        $this->subject->expects($this->once())
            ->method('logNotice')
            ->with($expectedNotice);
        $records = [];
        $result = [];

        self::assertTrue($this->subject->process(['schema' => ['file' => $schemaFile]], $records, $result));
    }

    public static function includeNotAllowedDataProvider(): array
    {
        return [
            'URL from host that is not allowed' => ['https://other.example.org/types.xsd'],
            'outside of the allowed directories' => ['../../outside.xsd'],
            'other file type' => ['types.xml'],
        ];
    }

    #[DataProvider('includeNotAllowedDataProvider')]
    public function testProcessThrowsExceptionForIncludeThatIsNotAllowed(string $location): void
    {
        $this->writeFile('types.xml', self::INCLUDED_SCHEMA);
        $schemaFile = $this->writeFile('schema.xsd', sprintf(self::SCHEMA_WITH_INCLUDE, $location));
        $this->subject = $this->getMockBuilder(ValidateXML::class)
            ->onlyMethods(['loadResource'])
            ->setConstructorArgs([new XMLReader(), $this->pathValidator, $this->messageContainer])
            ->getMock();
        $this->subject->setResourceAccess($this->createResourceAccess());
        $this->subject->method('loadResource')->willReturn('<item/>');
        $records = [];
        $result = [];

        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(XmlSchema::ERROR_INCLUDE_NOT_ALLOWED);

        $this->subject->process(['schema' => ['file' => $schemaFile]], $records, $result);
    }

    public function testProcessLogsNoticeIfValidationOfXMLFails(): void
    {
        $configuration = [];
        $records = [];
        $result = [];

        $validXML = 'bar';
        $this->subject = $this->getMockBuilder(ValidateXML::class)
            ->onlyMethods(['loadResource', 'logNotice'])
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
            ->with(
                ...[
                    1_508_776_068,
                    ['was', 0, 'error'],
                    [],
                ]
            );

        $this->subject->process($configuration, $records, $result);
    }
}
