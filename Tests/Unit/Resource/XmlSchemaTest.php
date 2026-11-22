<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Resource;

/**
 * This file is part of the TYPO3 CMS project.
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

use CPSIT\T3importExport\Exception\MissingResourceException;
use CPSIT\T3importExport\Resource\XmlSchema;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class XmlSchemaTest extends TestCase
{
    private const MAIN = <<<'XSD'
        <?xml version="1.0" encoding="utf-8"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
          <xs:include schemaLocation="%s"/>
          <xs:element name="item" type="itemType"/>
        </xs:schema>
        XSD;

    private const INCLUDED = <<<'XSD'
        <?xml version="1.0" encoding="utf-8"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
          <xs:complexType name="itemType">
            <xs:sequence>
              <xs:element name="title" type="xs:string"/>
            </xs:sequence>
          </xs:complexType>
        </xs:schema>
        XSD;

    private string $directory = '';

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/' . uniqid('t3import-export-xsd', true);
        GeneralUtility::mkdir_deep($this->directory . '/sub');
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->directory, true);
    }

    private static function isValid(XmlSchema $schema, string $uri, string $xml): bool
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new \DOMDocument();
            $document->loadXML($xml);

            return $schema->run(static fn(): bool => $document->schemaValidate($uri));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    public function testResolvesParentSegmentsBelowTheDirectoryOfTheSchema(): void
    {
        file_put_contents($this->directory . '/types.xsd', self::INCLUDED);
        $content = \sprintf(self::MAIN, 'sub/../types.xsd');
        file_put_contents($this->directory . '/main.xsd', $content);
        $subject = new XmlSchema();

        $uri = $subject->load($content, $this->directory . '/main.xsd');

        self::assertTrue(self::isValid($subject, $uri, '<item><title>a</title></item>'));
    }

    public function testDeniesIncludesOfSchemaLoadedFromUrl(): void
    {
        file_put_contents($this->directory . '/types.xsd', self::INCLUDED);

        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(XmlSchema::ERROR_INCLUDE_NOT_ALLOWED);

        (new XmlSchema())->load(\sprintf(self::MAIN, $this->directory . '/types.xsd'), null);
    }

    public function testDoesNotLoadOtherFilesWhileRunning(): void
    {
        file_put_contents($this->directory . '/types.xsd', self::INCLUDED);
        $subject = new XmlSchema();
        $subject->load(self::INCLUDED, null);

        self::assertFalse(self::isValid($subject, $this->directory . '/types.xsd', '<item/>'));
    }

    public function testRestoresThePreviousEntityLoader(): void
    {
        $loader = static fn(): null => null;
        libxml_set_external_entity_loader($loader);
        try {
            XmlSchema::isolate(static fn(): bool => true);
            self::assertSame($loader, libxml_get_external_entity_loader());
        } finally {
            libxml_set_external_entity_loader(null);
        }
    }
}
