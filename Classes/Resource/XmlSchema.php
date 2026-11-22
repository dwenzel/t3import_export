<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Resource;

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

/**
 * Prepares an XML schema (XSD) for validation, so that libxml does not load
 * any file or URL itself.
 *
 * Schemas named in `xs:include`, `xs:import`, `xs:redefine` and
 * `xs:override` are loaded only if they are files of the type `xsd` below the
 * directory of the main schema file. URLs and all other files are denied; a
 * schema loaded from a URL cannot include other schemas. While `run()`
 * executes, the external entity loader of libxml serves only the loaded
 * schemas and denies everything else, e.g. external entities and DTDs of the
 * validated documents.
 *
 * @internal
 */
final class XmlSchema
{
    public const ERROR_INCLUDE_NOT_ALLOWED = 1795300201;

    /**
     * Maximum number of schema documents, including the main schema
     */
    public const MAX_DOCUMENTS = 100;

    public const INCLUDE_FILE_EXTENSION = 'xsd';

    private const NAMESPACE_XSD = 'http://www.w3.org/2001/XMLSchema';
    private const URI_PREFIX = 't3import-export-schema://document/';

    /**
     * @var array<string, string> content of the loaded schemas by their URI
     */
    private array $documents = [];

    /**
     * Prepares the schema and the schemas it includes or imports.
     *
     * @param string $content content of the main schema
     * @param string|null $file absolute path of the main schema file; null if
     *                          it was loaded from a URL (no includes allowed)
     * @return string URI of the main schema, to be passed to libxml within `run()`
     * @throws MissingResourceException if an included schema is not allowed or cannot be loaded
     */
    public function load(string $content, ?string $file): string
    {
        $this->documents = [];
        $root = null;
        $current = null;
        if ($file !== null) {
            $current = realpath($file);
            $root = $current === false ? null : \dirname($current);
            $current = $current === false ? null : $current;
        }
        /** @var array<string, string> $uris URI by absolute path; the main schema has the key '' */
        $uris = [(string)$current => self::uri(0)];
        $pending = [[(string)$current, $content]];

        while ($pending !== []) {
            [$current, $content] = array_shift($pending);
            $document = self::parse($content);
            if ($document === null) {
                // not well-formed: libxml reports the errors when the schema is used
                $this->documents[$uris[$current]] = $content;
                continue;
            }
            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('xs', self::NAMESPACE_XSD);
            $references = $xpath->query(
                '/xs:schema/*[self::xs:include or self::xs:import or self::xs:redefine or self::xs:override]/@schemaLocation'
            );
            foreach ($references ?: [] as $attribute) {
                if (!$attribute instanceof \DOMAttr) {
                    continue;
                }
                $location = trim($attribute->value);
                $reference = self::resolve($root, $current, $location);
                if (!isset($uris[$reference])) {
                    if (\count($uris) >= self::MAX_DOCUMENTS) {
                        throw new MissingResourceException(
                            \sprintf('The schema includes more than %d schemas.', self::MAX_DOCUMENTS),
                            self::ERROR_INCLUDE_NOT_ALLOWED
                        );
                    }
                    $included = file_get_contents($reference);
                    if ($included === false || trim($included) === '') {
                        throw self::notAllowed($location, 'the file is empty or cannot be read');
                    }
                    $uris[$reference] = self::uri(\count($uris));
                    $pending[] = [$reference, $included];
                }
                $attribute->value = $uris[$reference];
            }
            $this->documents[$uris[$current]] = (string)$document->saveXML();
        }

        return self::uri(0);
    }

    /**
     * Executes the callback while the external entity loader of libxml
     * serves only the loaded schemas.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function run(callable $callback): mixed
    {
        return self::withDocuments($this->documents, $callback);
    }

    /**
     * Executes the callback while the external entity loader of libxml
     * denies all files and URLs, e.g. external entities and DTDs.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public static function isolate(callable $callback): mixed
    {
        return self::withDocuments([], $callback);
    }

    /**
     * @template T
     * @param array<string, string> $documents
     * @param callable(): T $callback
     * @return T
     */
    private static function withDocuments(array $documents, callable $callback): mixed
    {
        $previous = libxml_get_external_entity_loader();
        libxml_set_external_entity_loader(
            static function (?string $publicId, ?string $systemId) use ($documents) {
                if ($systemId === null || !isset($documents[$systemId])) {
                    return null;
                }
                $stream = fopen('php://memory', 'w+b');
                if ($stream === false) {
                    return null;
                }
                fwrite($stream, $documents[$systemId]);
                rewind($stream);

                return $stream;
            }
        );
        try {
            return $callback();
        } finally {
            libxml_set_external_entity_loader($previous);
        }
    }

    /**
     * Absolute path of an included schema
     *
     * @throws MissingResourceException if the location is not a file of the type xsd below `$root`
     */
    private static function resolve(?string $root, string $current, string $location): string
    {
        if ($root === null || $current === '') {
            throw self::notAllowed($location, 'a schema loaded from a URL cannot include other schemas');
        }
        if ($location === '' || str_contains($location, "\0") || preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', $location) === 1) {
            throw self::notAllowed($location, 'only files below the directory of the schema can be included');
        }
        $path = str_starts_with($location, '/') ? $location : \dirname($current) . '/' . $location;
        $absolutePath = realpath($path);
        if ($absolutePath === false || !is_file($absolutePath) || !str_starts_with($absolutePath, $root . '/')) {
            throw self::notAllowed($location, 'only files below the directory of the schema can be included');
        }
        if (strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION)) !== self::INCLUDE_FILE_EXTENSION) {
            throw self::notAllowed($location, \sprintf('only files of the type %s can be included', self::INCLUDE_FILE_EXTENSION));
        }

        return $absolutePath;
    }

    private static function notAllowed(string $location, string $reason): MissingResourceException
    {
        return new MissingResourceException(
            \sprintf('The included schema "%s" cannot be loaded: %s.', str_replace("\0", '\0', $location), $reason),
            self::ERROR_INCLUDE_NOT_ALLOWED
        );
    }

    private static function parse(string $content): ?\DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new \DOMDocument();
            $loaded = self::isolate(static fn(): bool => $document->loadXML($content, LIBXML_NONET));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $loaded ? $document : null;
    }

    private static function uri(int $number): string
    {
        return self::URI_PREFIX . $number . '.xsd';
    }
}
