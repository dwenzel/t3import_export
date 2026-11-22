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

use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\ImportExportCore\Exception\MissingResourceException;

/**
 * Loads an XML schema (XSD) and the schemas it names in `xs:include`,
 * `xs:import`, `xs:redefine` and `xs:override` with the file rules of the
 * extension (`ResourceAccess`): files only from the allowed directories (or
 * `EXT:` paths) with the file type `xsd`, URLs only from the allowed hosts.
 * Relative locations are resolved against the location of the schema that
 * names them.
 *
 * libxml does not load any file or URL itself: while `run()` executes, its
 * external entity loader serves only the loaded schemas and denies
 * everything else, e.g. external entities and DTDs of the validated
 * documents.
 *
 * @internal
 */
final class XmlSchema
{
    public const int ERROR_INCLUDE_NOT_ALLOWED = 1795300201;

    /**
     * Maximum number of schema documents, including the main schema
     */
    public const int MAX_DOCUMENTS = 100;

    public const string INCLUDE_FILE_EXTENSION = 'xsd';

    private const string NAMESPACE_XSD = 'http://www.w3.org/2001/XMLSchema';
    private const string URI_PREFIX = 't3import-export-schema://document/';

    /**
     * @var array<string, string> content of the loaded schemas by their URI
     */
    private array $documents = [];

    public function __construct(private readonly ResourceAccess $resourceAccess) {}

    /**
     * Loads the schema at `$location` (file path or URL) and the schemas it
     * includes or imports.
     *
     * @param int $missingCode code of the exception if the schema is missing or empty
     * @return string URI of the main schema, to be passed to libxml within `run()`
     * @throws InvalidArgumentException if the file of the main schema is not allowed
     * @throws MissingResourceException if a schema is missing, empty, cannot be loaded or is not allowed
     */
    public function load(string $location, int $missingCode): string
    {
        $this->documents = [];
        /** @var array<string, string> $uris URI by location */
        $uris = [$location => self::uri(0)];
        $content = $this->resourceAccess->load($location);
        if ($content === null || trim($content) === '') {
            throw new MissingResourceException(
                \sprintf('The schema "%s" is missing or empty.', $location),
                $missingCode
            );
        }
        $pending = [[$location, $content]];

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
                $reference = self::resolve($current, trim($attribute->value));
                if (!isset($uris[$reference])) {
                    if (\count($uris) >= self::MAX_DOCUMENTS) {
                        throw new MissingResourceException(
                            \sprintf('The schema "%s" includes more than %d schemas.', $location, self::MAX_DOCUMENTS),
                            self::ERROR_INCLUDE_NOT_ALLOWED
                        );
                    }
                    $uris[$reference] = self::uri(\count($uris));
                    $pending[] = [$reference, $this->loadIncluded($reference, $current)];
                }
                $attribute->value = $uris[$reference];
            }
            $this->documents[$uris[$current]] = (string)$document->saveXML();
        }

        return $uris[$location];
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
     * @throws MissingResourceException
     */
    private function loadIncluded(string $location, string $includedBy): string
    {
        try {
            if (ResourceAccess::isUrl($location)) {
                $this->resourceAccess->assertUrlAllowed($location);
            } elseif (strtolower(pathinfo($location, PATHINFO_EXTENSION)) !== self::INCLUDE_FILE_EXTENSION) {
                throw new InvalidArgumentException(
                    \sprintf('only files of the type %s can be included.', self::INCLUDE_FILE_EXTENSION),
                    Typo3PathResolver::ERROR_FILE_TYPE_NOT_ALLOWED
                );
            }
            $content = $location === '' ? null : $this->resourceAccess->load($location);
        } catch (InvalidArgumentException|MissingResourceException $exception) {
            throw new MissingResourceException(
                \sprintf('The schema "%s" included by "%s" cannot be loaded: %s', $location, $includedBy, $exception->getMessage()),
                self::ERROR_INCLUDE_NOT_ALLOWED,
                $exception
            );
        }
        if ($content === null || trim($content) === '') {
            throw new MissingResourceException(
                \sprintf('The schema "%s" included by "%s" is missing or empty.', $location, $includedBy),
                self::ERROR_INCLUDE_NOT_ALLOWED
            );
        }

        return $content;
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

    /**
     * Resolves a schema location relative to the location of the schema
     * naming it. Absolute paths and URLs are kept, the file rules decide on
     * them.
     */
    private static function resolve(string $base, string $location): string
    {
        if ($location === '' || ResourceAccess::isUrl($location)) {
            return $location;
        }
        if (ResourceAccess::isUrl($base)) {
            $parts = parse_url($base);
            if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
                return $location;
            }
            $origin = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
            $path = str_starts_with($location, '/') ? $location : self::directory($parts['path'] ?? '/') . $location;

            return $origin . self::normalize('/' . ltrim($path, '/'));
        }
        if (
            str_starts_with($location, '/')
            || str_starts_with($location, '\\')
            || str_starts_with($location, 'EXT:')
            || preg_match('/^[A-Za-z]:/', $location) === 1
        ) {
            return $location;
        }

        return self::normalize(self::directory($base) . $location);
    }

    /**
     * The path up to and including its last slash
     */
    private static function directory(string $path): string
    {
        $position = strrpos($path, '/');

        return $position === false ? '' : substr($path, 0, $position + 1);
    }

    /**
     * Removes `.` and `..` segments. A leading slash is kept. `..` segments
     * that would leave the root are kept, so that the file rules reject them.
     */
    private static function normalize(string $path): string
    {
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..' && $segments !== [] && end($segments) !== '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }

        return (str_starts_with($path, '/') ? '/' : '') . implode('/', $segments);
    }
}
