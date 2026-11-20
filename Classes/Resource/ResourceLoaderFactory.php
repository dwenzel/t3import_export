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

use CPSIT\ImportExportCore\Resource\HttpResourceLoader;
use Psr\Http\Client\ClientInterface;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Creates the loader for `url` of the file sources with the HTTP client of
 * TYPO3 and the allowed hosts of
 * `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts']`:
 *
 * - not set or empty: no URL is loaded
 * - `['data.example.org', '*.example.com']`: these hosts, `*.` for subdomains
 * - `['*']`: all hosts
 */
final readonly class ResourceLoaderFactory
{
    public function __construct(
        private ClientInterface $client,
        private RequestFactory $requestFactory,
    ) {}

    public function createHttpResourceLoader(): HttpResourceLoader
    {
        return new HttpResourceLoader($this->client, $this->requestFactory, self::allowedHosts());
    }

    /**
     * @return list<string>|null null: all hosts
     */
    public static function allowedHosts(): ?array
    {
        $configured = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts'] ?? [];
        if (!\is_array($configured)) {
            return [];
        }
        $hosts = array_values(array_filter(
            $configured,
            static fn(mixed $host): bool => \is_string($host) && trim($host) !== ''
        ));

        return \in_array('*', $hosts, true) ? null : $hosts;
    }
}
