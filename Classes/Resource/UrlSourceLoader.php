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

use CPSIT\T3importExport\Exception\InvalidArgumentException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Loads source files from URLs with the request factory of TYPO3 (the HTTP
 * settings of TYPO3 apply, e.g. proxy and timeout).
 *
 * Every URL is checked with the `FileSourceGuard` (http or https, allowed
 * host) before the request is sent. The HTTP client does not follow
 * redirects: the loader follows at most `MAX_REDIRECTS` and checks the target
 * of each like the URL. A redirect from https to http is not followed.
 */
final readonly class UrlSourceLoader
{
    public const MAX_REDIRECTS = 5;

    private const REDIRECT_STATUS = [301, 302, 303, 307, 308];

    public function __construct(
        private RequestFactory $requestFactory,
        private FileSourceGuard $fileSourceGuard,
    ) {}

    /**
     * @return StreamInterface|null the body of the response; null if the URL cannot be loaded
     * @throws InvalidArgumentException if the URL or a redirect is not allowed
     */
    public function open(string $url): ?StreamInterface
    {
        $previous = null;
        for ($redirects = 0; ; $redirects++) {
            $this->fileSourceGuard->assertUrlAllowed($url);
            if (
                $previous !== null
                && strtolower((string)parse_url($previous, PHP_URL_SCHEME)) === 'https'
                && strtolower((string)parse_url($url, PHP_URL_SCHEME)) !== 'https'
            ) {
                return null;
            }
            try {
                $response = $this->requestFactory->request(
                    $url,
                    'GET',
                    ['allow_redirects' => false, 'http_errors' => false]
                );
            } catch (GuzzleException) {
                return null;
            }
            $status = $response->getStatusCode();
            $target = trim($response->getHeaderLine('Location'));
            if (!\in_array($status, self::REDIRECT_STATUS, true) || $target === '') {
                break;
            }
            $response->getBody()->close();
            if ($redirects >= self::MAX_REDIRECTS) {
                return null;
            }
            $previous = $url;
            try {
                $url = (string)UriResolver::resolve(new Uri($url), new Uri($target))->withFragment('');
            } catch (\InvalidArgumentException) {
                return null;
            }
        }
        if ($status < 200 || $status >= 300) {
            $response->getBody()->close();

            return null;
        }

        return $response->getBody();
    }
}
