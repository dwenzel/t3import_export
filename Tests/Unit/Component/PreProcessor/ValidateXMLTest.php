<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

use CPSIT\ImportExportCore\Exception\MissingResourceException;
use CPSIT\ImportExportCore\Messaging\MessageContainer;
use CPSIT\ImportExportCore\Resource\HttpResourceLoader;
use CPSIT\T3importExport\Component\PreProcessor\ValidateXML;
use CPSIT\T3importExport\Resource\AllowedHosts;
use CPSIT\T3importExport\Resource\ResourceAccess;
use CPSIT\T3importExport\Resource\Typo3PathResolver;
use CPSIT\T3importExport\Resource\XmlSchema;
use CPSIT\T3importExport\Validation\Configuration\ResourcePathConfigurationValidator;
use DOMDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

    protected ValidateXML $subject;
    /**
     * @var list<string>
     */
    protected array $files = [];
    protected MessageContainer $messageContainer;
    public string $schemaFile = '';

    protected function setUp(): void
    {
        $this->messageContainer = new MessageContainer();
        $this->subject = $this->createSubject($this->createStub(ResourcePathConfigurationValidator::class));
    }

    protected function createSubject(ResourcePathConfigurationValidator $pathValidator): ValidateXML
    {
        $subject = new ValidateXML(null, $pathValidator, $this->messageContainer);
        $subject->setResourceAccess($this->createResourceAccess());

        return $subject;
    }

    protected function createResourceAccess(): ResourceAccess
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('sendRequest');

        return new ResourceAccess(
            new Typo3PathResolver(directories: [self::schemaDirectory()]),
            new HttpResourceLoader($client, $this->createStub(RequestFactoryInterface::class), ['data.example.org']),
            new AllowedHosts(['data.example.org'])
        );
    }

    protected static function schemaDirectory(): string
    {
        return Environment::getVarPath() . '/import/tests';
    }

    protected function tearDown(): void
    {
        foreach ([$this->schemaFile, ...$this->files] as $file) {
            if ($file !== '' && is_file($file)) {
                unlink($file);
            }
        }
    }

    protected function useSchemaFile(string $content): array
    {
        GeneralUtility::mkdir_deep(self::schemaDirectory());
        $this->schemaFile = self::schemaDirectory() . '/' . uniqid('schema', true) . '.xsd';
        file_put_contents($this->schemaFile, $content);

        return ['file' => $this->schemaFile];
    }

    protected function writeFile(string $path, string $content): string
    {
        GeneralUtility::mkdir_deep(dirname($path));
        file_put_contents($path, $content);
        $this->files[] = $path;

        return $path;
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

    #[Test]
    public function processThrowsExceptionIfSchemaFileIsNotAllowed(): void
    {
        $configuration = ['fields' => 'foo', 'schema' => ['file' => Environment::getPublicPath() . '/index.php']];
        $record = ['uid' => 1, 'foo' => '<item/>'];

        $this->expectException(\CPSIT\ImportExportCore\Exception\InvalidArgumentException::class);
        $this->expectExceptionCode(Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED);

        $this->subject->process($configuration, $record);
    }

    #[Test]
    public function processUsesIncludedSchemaNextToTheSchema(): void
    {
        $name = uniqid('types', true) . '.xsd';
        $this->writeFile(self::schemaDirectory() . '/' . $name, self::INCLUDED_SCHEMA);
        $configuration = ['fields' => 'foo', 'schema' => $this->useSchemaFile(sprintf(self::SCHEMA_WITH_INCLUDE, $name))];

        $valid = ['uid' => 1, 'foo' => '<item><title>a</title></item>'];
        $invalid = ['uid' => 2, 'foo' => '<item><name>a</name></item>'];

        self::assertTrue($this->subject->process($configuration, $valid));
        self::assertFalse($this->subject->process($configuration, $invalid));
    }

    public static function includeNotAllowedDataProvider(): iterable
    {
        yield 'URL from host that is not allowed' => ['https://other.example.org/types.xsd'];
        yield 'file URL' => ['file://' . Environment::getVarPath() . '/import/tests/types.xsd'];
        yield 'outside of the allowed directories' => ['../outside-' . uniqid('', true) . '.xsd'];
        yield 'absolute path outside of the allowed directories' => [Environment::getPublicPath() . '/types.xsd'];
        yield 'other file type' => ['types.xml'];
    }

    #[Test]
    #[DataProvider('includeNotAllowedDataProvider')]
    public function processThrowsExceptionForIncludeThatIsNotAllowed(string $location): void
    {
        if (str_starts_with($location, '../')) {
            $this->writeFile(Environment::getVarPath() . '/import/' . substr($location, 3), self::INCLUDED_SCHEMA);
        } elseif (!str_contains($location, '://')) {
            $this->writeFile(str_starts_with($location, '/') ? $location : self::schemaDirectory() . '/' . $location, self::INCLUDED_SCHEMA);
        }
        $configuration = ['fields' => 'foo', 'schema' => $this->useSchemaFile(sprintf(self::SCHEMA_WITH_INCLUDE, $location))];
        $record = ['uid' => 1, 'foo' => '<item><title>a</title></item>'];

        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(XmlSchema::ERROR_INCLUDE_NOT_ALLOWED);

        $this->subject->process($configuration, $record);
    }

    #[Test]
    public function processDoesNotExpandExternalEntities(): void
    {
        $secret = $this->writeFile(self::schemaDirectory() . '/' . uniqid('secret', true) . '.txt', 'secret-content');
        $document = new DOMDocument();
        $this->subject = new ValidateXML($document, $this->createStub(ResourcePathConfigurationValidator::class), $this->messageContainer);
        $this->subject->setResourceAccess($this->createResourceAccess());
        $xml = '<?xml version="1.0"?><!DOCTYPE item [<!ENTITY e SYSTEM "file://' . $secret . '">]>'
            . '<item><title>&e;</title></item>';

        foreach ([['fields' => 'foo'], ['fields' => 'foo', 'schema' => $this->useSchemaFile(self::SCHEMA)]] as $configuration) {
            $record = ['uid' => 1, 'foo' => $xml];
            $this->subject->process($configuration, $record);

            self::assertStringNotContainsString('secret-content', (string)$document->saveXML());
        }
    }
}
