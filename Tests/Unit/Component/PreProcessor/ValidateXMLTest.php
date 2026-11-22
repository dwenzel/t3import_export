<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

use CPSIT\T3importExport\Component\PreProcessor\ValidateXML;
use CPSIT\T3importExport\Exception\MissingResourceException;
use CPSIT\T3importExport\Messaging\MessageContainer;
use CPSIT\T3importExport\Resource\XmlSchema;
use CPSIT\T3importExport\Validation\Configuration\ResourcePathConfigurationValidator;
use DOMDocument;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Schemas included by the schema of the ValidateXML pre-processor
 */
class ValidateXMLTest extends TestCase
{
    protected const SCHEMA = <<<'XSD'
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
    protected DOMDocument $document;
    protected ValidateXML $subject;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/' . uniqid('t3import-export-xsd', true);
        GeneralUtility::mkdir_deep($this->directory . '/schemas/sub');
        $this->document = new DOMDocument();
        $directory = $this->directory;
        $this->subject = new class ($this->document, $this->createMock(ResourcePathConfigurationValidator::class), new MessageContainer(), $directory) extends ValidateXML {
            private string $directory;

            public function __construct(
                DOMDocument $document,
                ResourcePathConfigurationValidator $pathConfigurationValidator,
                MessageContainer $messageContainer,
                string $directory
            ) {
                parent::__construct($document, $pathConfigurationValidator, $messageContainer);
                $this->directory = $directory;
            }

            /**
             * Resolves every path to `schemas/main.xsd` of the test directory
             */
            protected function getAbsoluteFilePath($path)
            {
                return $this->directory . '/schemas/main.xsd';
            }
        };
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->directory, true);
    }

    /**
     * @param array<string, string> $files content by path relative to the test directory
     */
    protected function writeFiles(array $files): array
    {
        foreach ($files as $path => $content) {
            file_put_contents($this->directory . '/' . $path, $content);
        }

        return ['file' => 'EXT:foo/schema.xsd'];
    }

    public function testProcessUsesIncludedSchemaBelowTheDirectoryOfTheSchema(): void
    {
        $schema = $this->writeFiles([
            'schemas/main.xsd' => sprintf(self::SCHEMA_WITH_INCLUDE, 'sub/types.xsd'),
            'schemas/sub/types.xsd' => self::INCLUDED_SCHEMA,
        ]);
        $configuration = ['fields' => 'foo', 'schema' => $schema];
        $valid = ['uid' => 1, 'foo' => '<item><title>a</title></item>'];
        $invalid = ['uid' => 2, 'foo' => '<item><name>a</name></item>'];

        self::assertTrue($this->subject->process($configuration, $valid));
        self::assertFalse($this->subject->process($configuration, $invalid));
    }

    public function includeNotAllowedDataProvider(): array
    {
        return [
            'URL' => ['https://example.org/types.xsd'],
            'file URL' => ['file:///etc/types.xsd'],
            'outside of the directory of the schema' => ['../types.xsd'],
            'absolute path outside of the directory of the schema' => ['/etc/passwd'],
            'other file type' => ['sub/types.xml'],
            'missing file' => ['sub/missing.xsd'],
        ];
    }

    /**
     * @dataProvider includeNotAllowedDataProvider
     */
    public function testProcessThrowsExceptionForIncludeThatIsNotAllowed(string $location): void
    {
        $schema = $this->writeFiles([
            'schemas/main.xsd' => sprintf(self::SCHEMA_WITH_INCLUDE, $location),
            'types.xsd' => self::INCLUDED_SCHEMA,
            'schemas/sub/types.xml' => self::INCLUDED_SCHEMA,
        ]);
        $record = ['uid' => 1, 'foo' => '<item><title>a</title></item>'];

        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(XmlSchema::ERROR_INCLUDE_NOT_ALLOWED);

        $this->subject->process(['fields' => 'foo', 'schema' => $schema], $record);
    }

    public function testProcessDoesNotExpandExternalEntities(): void
    {
        $schema = $this->writeFiles([
            'schemas/main.xsd' => self::SCHEMA,
            'schemas/secret.xsd' => 'secret-content',
        ]);
        $xml = '<?xml version="1.0"?><!DOCTYPE item [<!ENTITY e SYSTEM "file://' . $this->directory . '/schemas/secret.xsd">]>'
            . '<item><title>&e;</title></item>';

        foreach ([['fields' => 'foo'], ['fields' => 'foo', 'schema' => $schema]] as $configuration) {
            $record = ['uid' => 1, 'foo' => $xml];
            $this->subject->process($configuration, $record);

            self::assertStringNotContainsString('secret-content', (string)$this->document->saveXML());
        }
    }
}
