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

use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\ImportExportCore\Exception\MissingResourceException;
use CPSIT\ImportExportCore\Resource\HttpResourceLoader;
use CPSIT\T3importExport\Resource\ResourceAccess;
use CPSIT\T3importExport\Resource\Typo3PathResolver;
use CPSIT\T3importExport\Resource\XmlSchema;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[CoversClass(XmlSchema::class)]
class XmlSchemaTest extends TestCase
{
    private const string MAIN = <<<'XSD'
        <?xml version="1.0" encoding="utf-8"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
          <xs:include schemaLocation="%s"/>
          <xs:element name="item" type="itemType"/>
        </xs:schema>
        XSD;

    private const string INCLUDED = <<<'XSD'
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

    /**
     * @var list<RequestInterface>
     */
    private array $requests = [];

    protected function setUp(): void
    {
        $this->directory = Environment::getVarPath() . '/import/tests/' . uniqid('schema', true);
        GeneralUtility::mkdir_deep($this->directory . '/sub');
        GeneralUtility::mkdir_deep(Environment::getVarPath() . '/import/outside');
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->directory, true);
        GeneralUtility::rmdir(Environment::getVarPath() . '/import/outside', true);
    }

    private function createSubject(): XmlSchema
    {
        $client = $this->createStub(ClientInterface::class);
        $client->method('sendRequest')->willReturnCallback(function (RequestInterface $request): Response {
            $this->requests[] = $request;

            return new Response(200, [], self::INCLUDED);
        });

        return new XmlSchema(new ResourceAccess(
            new Typo3PathResolver(directories: [Environment::getVarPath() . '/import/tests']),
            new HttpResourceLoader($client, new HttpFactory(), ['data.example.org'])
        ));
    }

    private function write(string $name, string $content): string
    {
        $path = $this->directory . '/' . $name;
        file_put_contents($path, $content);

        return $path;
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

    #[Test]
    public function loadsIncludedSchemaNextToTheSchema(): void
    {
        $this->write('sub/types.xsd', self::INCLUDED);
        $main = $this->write('main.xsd', \sprintf(self::MAIN, 'sub/./types.xsd'));
        $subject = $this->createSubject();

        $uri = $subject->load($main, 1);

        self::assertTrue(self::isValid($subject, $uri, '<item><title>a</title></item>'));
        self::assertFalse(self::isValid($subject, $uri, '<item><name>a</name></item>'));
    }

    #[Test]
    public function resolvesParentSegmentsInsideTheAllowedDirectory(): void
    {
        $this->write('types.xsd', self::INCLUDED);
        $main = $this->write('sub/main.xsd', \sprintf(self::MAIN, '../types.xsd'));
        $subject = $this->createSubject();

        $uri = $subject->load($main, 1);

        self::assertTrue(self::isValid($subject, $uri, '<item><title>a</title></item>'));
    }

    #[Test]
    public function deniesIncludeOutsideOfTheAllowedDirectories(): void
    {
        file_put_contents(Environment::getVarPath() . '/import/outside/types.xsd', self::INCLUDED);
        $main = $this->write('main.xsd', \sprintf(self::MAIN, '../../outside/types.xsd'));

        try {
            $this->createSubject()->load($main, 1);
            self::fail('Exception expected');
        } catch (MissingResourceException $exception) {
            self::assertSame(XmlSchema::ERROR_INCLUDE_NOT_ALLOWED, $exception->getCode());
            self::assertInstanceOf(InvalidArgumentException::class, $exception->getPrevious());
            self::assertSame(Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED, $exception->getPrevious()->getCode());
        }
    }

    #[Test]
    public function deniesIncludeWithAbsolutePathOutsideOfTheAllowedDirectories(): void
    {
        $main = $this->write('main.xsd', \sprintf(self::MAIN, Environment::getPublicPath() . '/index.xsd'));

        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(XmlSchema::ERROR_INCLUDE_NOT_ALLOWED);

        $this->createSubject()->load($main, 1);
    }

    #[Test]
    public function deniesIncludeOfOtherFileTypes(): void
    {
        $this->write('types.xml', self::INCLUDED);
        $main = $this->write('main.xsd', \sprintf(self::MAIN, 'types.xml'));

        try {
            $this->createSubject()->load($main, 1);
            self::fail('Exception expected');
        } catch (MissingResourceException $exception) {
            self::assertSame(XmlSchema::ERROR_INCLUDE_NOT_ALLOWED, $exception->getCode());
            self::assertSame(Typo3PathResolver::ERROR_FILE_TYPE_NOT_ALLOWED, $exception->getPrevious()?->getCode());
        }
    }

    #[Test]
    public function deniesIncludeWithFileUrl(): void
    {
        $types = $this->write('types.xsd', self::INCLUDED);
        $main = $this->write('main.xsd', \sprintf(self::MAIN, 'file://' . $types));

        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(XmlSchema::ERROR_INCLUDE_NOT_ALLOWED);

        $this->createSubject()->load($main, 1);
    }

    #[Test]
    public function deniesIncludeFromHostThatIsNotAllowed(): void
    {
        $main = $this->write('main.xsd', \sprintf(self::MAIN, 'https://other.example.org/types.xsd'));

        try {
            $this->createSubject()->load($main, 1);
            self::fail('Exception expected');
        } catch (MissingResourceException $exception) {
            self::assertSame(XmlSchema::ERROR_INCLUDE_NOT_ALLOWED, $exception->getCode());
        }
        self::assertSame([], $this->requests);
    }

    #[Test]
    public function loadsIncludeFromAllowedHost(): void
    {
        $main = $this->write('main.xsd', \sprintf(self::MAIN, 'https://data.example.org/schemas/types.xsd'));
        $subject = $this->createSubject();

        $uri = $subject->load($main, 1);

        self::assertCount(1, $this->requests);
        self::assertSame('https://data.example.org/schemas/types.xsd', (string)$this->requests[0]->getUri());
        self::assertTrue(self::isValid($subject, $uri, '<item><title>a</title></item>'));
    }

    #[Test]
    public function resolvesIncludeRelativeToTheUrlOfTheSchema(): void
    {
        $client = $this->createStub(ClientInterface::class);
        $client->method('sendRequest')->willReturnCallback(function (RequestInterface $request): Response {
            $this->requests[] = $request;

            return new Response(200, [], str_ends_with((string)$request->getUri(), 'main.xsd')
                ? \sprintf(self::MAIN, '../common/types.xsd')
                : self::INCLUDED);
        });
        $subject = new XmlSchema(new ResourceAccess(
            new Typo3PathResolver(directories: [Environment::getVarPath() . '/import/tests']),
            new HttpResourceLoader($client, new HttpFactory(), ['data.example.org'])
        ));

        $uri = $subject->load('https://data.example.org/schemas/v1/main.xsd', 1);

        self::assertSame('https://data.example.org/schemas/common/types.xsd', (string)$this->requests[1]->getUri());
        self::assertTrue(self::isValid($subject, $uri, '<item><title>a</title></item>'));
    }

    #[Test]
    public function throwsExceptionWithGivenCodeForMissingSchema(): void
    {
        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(42);

        $this->createSubject()->load($this->directory . '/missing.xsd', 42);
    }

    #[Test]
    public function doesNotLoadSchemasThatAreNotIncludedWhileRunning(): void
    {
        $types = $this->write('types.xsd', self::INCLUDED);
        $main = $this->write('main.xsd', self::INCLUDED);
        $subject = $this->createSubject();
        $subject->load($main, 1);

        self::assertFalse(self::isValid($subject, $types, '<item/>'));
    }

    #[Test]
    public function doesNotExpandExternalEntities(): void
    {
        $secret = $this->write('secret.xsd', 'secret-content');
        $main = $this->write('main.xsd', self::INCLUDED);
        $subject = $this->createSubject();
        $uri = $subject->load($main, 1);
        $xml = '<?xml version="1.0"?><!DOCTYPE item [<!ENTITY e SYSTEM "file://' . $secret . '">]><item>&e;</item>';

        $previous = libxml_use_internal_errors(true);
        try {
            $document = new \DOMDocument();
            $subject->run(static function () use ($document, $xml, $uri): void {
                $document->loadXML($xml, LIBXML_NOENT | LIBXML_DTDLOAD);
                $document->schemaValidate($uri);
            });
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        self::assertStringNotContainsString('secret-content', (string)$document->saveXML());
    }

    #[Test]
    public function restoresThePreviousEntityLoader(): void
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
