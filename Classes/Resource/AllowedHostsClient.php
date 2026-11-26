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
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Sends only requests to allowed hosts and ports (see `AllowedHosts`).
 *
 * `HttpResourceLoader` of the core checks the host of each URL and of each
 * redirect target, but not the port. The loader sends every request, also
 * those for redirects, with this client, which refuses requests to a port
 * that is not allowed before they are sent.
 */
final readonly class AllowedHostsClient implements ClientInterface
{
    public function __construct(
        private ClientInterface $client,
        private AllowedHosts $allowedHosts,
    ) {}

    /**
     * @throws HostNotAllowedException if the host or port of the request is not allowed
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $url = (string)$request->getUri();
        if (!$this->allowedHosts->isAllowed($url)) {
            throw new HostNotAllowedException(
                \sprintf('The request to "%s" is not sent: the host or port is not allowed.', $url),
                HostNotAllowedException::ERROR_HOST_NOT_ALLOWED
            );
        }

        return $this->client->sendRequest($request);
    }
}
