<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

use CPSIT\ImportExportCore\Exception\MissingResourceException;
use CPSIT\ImportExportCore\Messaging\MessageContainer;
use CPSIT\T3importExport\Component\PreProcessor\ValidateXML;
use CPSIT\T3importExport\Validation\Configuration\ResourcePathConfigurationValidator;
use DOMDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Describes the behaviour of the ValidateXML pre-processor before it moves to the core
 * (CPS-IT/import-export-core#25)
 */
#[CoversClass(ValidateXML::class)]
class ValidateXMLTest extends TestCase
{
    protected const string SCHEMA = <<<'XSD'
        <?xml version="1.0" encoding="utf-8"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
          <xs:element name="item">
            <xs:complexType>
              <xs:sequence>
                <xs:element name="title" type="xs:string"/>
              </xs:sequence>
            </xs:complexType>
          </xs:element>
        </xs:schema>
        XSD;

    protected ValidateXML $subject;
    protected MessageContainer $messageContainer;
    public string $schemaFile = '';

    protected function setUp(): void
    {
        $this->messageContainer = new MessageContainer();
        $this->subject = $this->createSubject($this->createStub(ResourcePathConfigurationValidator::class));
    }

    /**
     * Resolves every resource path to the temporary schema file
     */
    protected function createSubject(ResourcePathConfigurationValidator $pathValidator): ValidateXML
    {
        $test = $this;
        return new class (null, $pathValidator, $this->messageContainer, $test) extends ValidateXML {
            public function __construct(
                ?DOMDocument $document,
                ?ResourcePathConfigurationValidator $pathConfigurationValidator,
                ?MessageContainer $messageContainer,
                private readonly ValidateXMLTest $test
            ) {
                parent::__construct($document, $pathConfigurationValidator, $messageContainer);
            }

            protected function getAbsoluteFilePath($path)
            {
                return $this->test->schemaFile;
            }
        };
    }

    protected function tearDown(): void
    {
        if ($this->schemaFile !== '' && is_file($this->schemaFile)) {
            unlink($this->schemaFile);
        }
    }

    protected function useSchemaFile(string $content): array
    {
        $this->schemaFile = (string)tempnam(sys_get_temp_dir(), 'xsd');
        file_put_contents($this->schemaFile, $content);

        return ['file' => 'EXT:foo/schema.xsd'];
    }

    public static function invalidConfigurationDataProvider(): iterable
    {
        yield 'empty' => [[]];
        yield 'fields empty' => [['fields' => '']];
        yield 'fields not a string' => [['fields' => ['foo']]];
    }

    #[Test]
    #[DataProvider('invalidConfigurationDataProvider')]
    public function isConfigurationValidReturnsFalseForInvalidConfiguration(array $configuration): void
    {
        self::assertFalse($this->subject->isConfigurationValid($configuration));
    }

    #[Test]
    public function isConfigurationValidReturnsTrueWithoutSchema(): void
    {
        $pathValidator = $this->createMock(ResourcePathConfigurationValidator::class);
        $pathValidator->expects(self::never())->method('isValid');
        $this->subject = $this->createSubject($pathValidator);

        self::assertTrue($this->subject->isConfigurationValid(['fields' => 'foo']));
    }

    #[Test]
    public function isConfigurationValidUsesPathValidatorForSchema(): void
    {
        $schema = ['file' => 'EXT:foo/schema.xsd'];
        $pathValidator = $this->createMock(ResourcePathConfigurationValidator::class);
        $pathValidator->expects(self::once())
            ->method('isValid')
            ->with($schema)
            ->willReturn(false);
        $this->subject = $this->createSubject($pathValidator);

        self::assertFalse($this->subject->isConfigurationValid(['fields' => 'foo', 'schema' => $schema]));
    }

    #[Test]
    public function processReturnsTrueForWellFormedXml(): void
    {
        $record = ['uid' => 1, 'foo' => '<item><title>a</title></item>'];

        self::assertTrue($this->subject->process(['fields' => 'foo'], $record));
        self::assertArrayNotHasKey(ValidateXML::KEY_VALIDATION_FAILED, $record);
        self::assertFalse($this->messageContainer->hasMessages());
    }

    #[Test]
    public function processAcceptsDomDocument(): void
    {
        $document = new DOMDocument();
        $document->loadXML('<item><title>a</title></item>');
        $record = ['uid' => 1, 'foo' => $document];

        self::assertTrue($this->subject->process(['fields' => 'foo'], $record));
    }

    #[Test]
    public function processMarksRecordAndLogsErrorForMalformedXml(): void
    {
        $record = ['uid' => 7, 'foo' => '<item><title>a</item>'];

        self::assertFalse($this->subject->process(['fields' => 'foo'], $record));
        self::assertTrue($record[ValidateXML::KEY_VALIDATION_FAILED]);
        self::assertTrue($this->messageContainer->hasMessageWithId(1_646_304_431));
        $information = $this->messageContainer->getMessages()[0]->getAdditionalInformation();
        self::assertStringContainsString('Error validating content of field foo', $information[0]);
        self::assertStringContainsString('Record ID 7', $information[0]);
    }

    #[Test]
    public function processUsesConfiguredIdentifierField(): void
    {
        $record = ['id' => 'abc', 'foo' => '<item>'];

        $this->subject->process(['fields' => 'foo', 'identifier' => 'id'], $record);

        $information = $this->messageContainer->getMessages()[0]->getAdditionalInformation();
        self::assertStringContainsString('Record ID abc', $information[0]);
    }

    public static function emptyValueDataProvider(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace' => ['  '];
        yield 'integer' => [5];
    }

    #[Test]
    #[DataProvider('emptyValueDataProvider')]
    public function processMarksRecordForEmptyValueWithoutMessage(mixed $value): void
    {
        $record = ['uid' => 1, 'foo' => $value];

        self::assertFalse($this->subject->process(['fields' => 'foo'], $record));
        self::assertTrue($record[ValidateXML::KEY_VALIDATION_FAILED]);
        self::assertFalse($this->messageContainer->hasMessages());
    }

    #[Test]
    public function processStopsAtFirstInvalidField(): void
    {
        $record = ['uid' => 1, 'foo' => '<a>', 'bar' => '<b>'];

        self::assertFalse($this->subject->process(['fields' => 'foo, bar'], $record));
        $information = $this->messageContainer->getMessages()[0]->getAdditionalInformation();
        self::assertStringContainsString('field foo', $information[0]);
        foreach ($this->messageContainer->getMessages() as $message) {
            self::assertStringNotContainsString('field bar', $message->getAdditionalInformation()[0]);
        }
    }

    #[Test]
    public function processValidatesAllFields(): void
    {
        $record = ['uid' => 1, 'foo' => '<a/>', 'bar' => '<b>'];

        self::assertFalse($this->subject->process(['fields' => 'foo,bar'], $record));
        $information = $this->messageContainer->getMessages()[0]->getAdditionalInformation();
        self::assertStringContainsString('field bar', $information[0]);
    }

    #[Test]
    public function processAcceptsXmlMatchingSchema(): void
    {
        $configuration = ['fields' => 'foo', 'schema' => $this->useSchemaFile(self::SCHEMA)];
        $record = ['uid' => 1, 'foo' => '<item><title>a</title></item>'];

        self::assertTrue($this->subject->process($configuration, $record));
    }

    #[Test]
    public function processRejectsXmlNotMatchingSchema(): void
    {
        $configuration = ['fields' => 'foo', 'schema' => $this->useSchemaFile(self::SCHEMA)];
        $record = ['uid' => 1, 'foo' => '<item><name>a</name></item>'];

        self::assertFalse($this->subject->process($configuration, $record));
        self::assertTrue($record[ValidateXML::KEY_VALIDATION_FAILED]);
        self::assertTrue($this->messageContainer->hasMessageWithId(1_646_304_431));
    }

    #[Test]
    public function processThrowsExceptionIfSchemaCanNotBeLoaded(): void
    {
        $configuration = ['fields' => 'foo', 'schema' => $this->useSchemaFile('')];
        $record = ['uid' => 1, 'foo' => '<item/>'];

        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(ValidateXML::MISSING_RESOURCE_CODE);

        $this->subject->process($configuration, $record);
    }
}
