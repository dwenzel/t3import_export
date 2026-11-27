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

/**
 * The hosts and ports from which URLs may be loaded
 * (`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts']`):
 *
 * - `example.org`: this host with the default port of the scheme (80 for
 *   http, 443 for https)
 * - `example.org:8080`: this host with port 8080 only
 * - `*.example.org`: the subdomains of example.org (optionally with a port)
 * - `*`: all hosts and ports
 *
 * Host names are compared without case and without a trailing dot. They are
 * not resolved, so a host name which resolves to an internal address is
 * allowed if it is listed.
 */
final readonly class AllowedHosts
{
    /**
     * @var list<array{host: string, port: int|null}>|null null: all hosts and ports
     */
    private ?array $entries;

    /**
     * @param list<string> $hosts entries `host`, `host:port`, `*.domain` or `*`
     */
    public function __construct(array $hosts)
    {
        $hosts = array_map(static fn(string $host): string => strtolower(trim($host)), $hosts);
        $this->entries = \in_array('*', $hosts, true) ? null : array_map(self::parse(...), $hosts);
    }

    /**
     * The configured hosts; no host if nothing is configured
     */
    public static function fromConfiguration(): self
    {
        $configured = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts'] ?? [];
        if (!\is_array($configured)) {
            return new self([]);
        }

        return new self(array_values(array_filter(
            $configured,
            static fn(mixed $host): bool => \is_string($host) && trim($host) !== ''
        )));
    }

    /**
     * Whether the host and port of a URL are allowed. An entry without port
     * allows only the default port of the scheme.
     */
    public function isAllowed(string $url): bool
    {
        $host = rtrim(strtolower((string)parse_url($url, PHP_URL_HOST)), '.');
        if ($host === '') {
            return false;
        }
        if ($this->entries === null) {
            return true;
        }
        $defaultPort = strtolower((string)parse_url($url, PHP_URL_SCHEME)) === 'https' ? 443 : 80;
        $port = parse_url($url, PHP_URL_PORT) ?? $defaultPort;
        foreach ($this->entries as $entry) {
            $hostMatches = $host === $entry['host']
                || (str_starts_with($entry['host'], '*.') && str_ends_with($host, substr($entry['host'], 1)));
            if ($hostMatches && $port === ($entry['port'] ?? $defaultPort)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The host names of the entries without ports, e.g. for the host check of
     * `HttpResourceLoader` of the core
     *
     * @return list<string>|null null: all hosts
     */
    public function hostNames(): ?array
    {
        if ($this->entries === null) {
            return null;
        }

        return array_values(array_unique(array_map(
            static fn(array $entry): string => $entry['host'],
            $this->entries
        )));
    }

    /**
     * @return array{host: string, port: int|null}
     */
    private static function parse(string $host): array
    {
        // [IPv6]:port, host:port; a bare IPv6 address has no port
        if (preg_match('/^(\[[^\]]+\]|[^:\[\]]+):(\d+)$/', $host, $matches) === 1) {
            return ['host' => rtrim($matches[1], '.'), 'port' => (int)$matches[2]];
        }

        return ['host' => rtrim($host, '.'), 'port' => null];
    }
}
