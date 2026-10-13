<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\JMAP;

use OCA\Mail\JMAP\Exception\JmapTransportException;
use OCP\Http\Client\IClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Adapts the Native HTTP client (IClient) to the PSR-18 (ClientInterface) contract.
 *
 * Nextcloud 34+ already implements ClientInterface on IClient; this wrapper
 * keeps the integration working on the older supported releases (32/33) and
 * pins the per-request options the JMAP transport relies on.
 */
class JmapClientAdapter implements ClientInterface {

	/**
	 * @param array<string, mixed> $defaultOptions Options applied to every
	 *                                             request, e.g. 'verify' and 'timeout'. SSRF protection stays on
	 *                                             unless explicitly disabled here.
	 */
	public function __construct(
		private IClient $client,
		private ResponseFactoryInterface $responseFactory,
		private StreamFactoryInterface $streamFactory,
		private array $defaultOptions = [],
	) {
	}

	#[\Override]
	public function sendRequest(RequestInterface $request): ResponseInterface {
		// convert PSR-7 request to native transport client request
		$options = $this->defaultOptions;
		foreach (array_keys($request->getHeaders()) as $name) {
			$options['headers'][$name] = $request->getHeaderLine($name);
		}
		$options['allow_redirects'] = false;
		$options['http_errors'] = false;
		// Always request a streamed response so the body is exposed as a live
		// resource rather than buffered into memory. Small responses are read
		// in full by the caller; large blob downloads stay streamed end-to-end.
		$options['stream'] = true;

		$body = (string)$request->getBody();
		if ($body !== '') {
			$options['body'] = $body;
		}

		// transceive and catch any transport-level exceptions
		try {
			$nativeResponse = $this->client->request(
				$request->getMethod(),
				(string)$request->getUri(),
				$options,
			);
		} catch (\Throwable $e) {
			throw new JmapTransportException($e->getMessage(), (int)$e->getCode(), $e);
		}

		// Convert the native transport client response to a PSR-7 response.
		$body = $nativeResponse->getBody();
		$stream = is_resource($body)
			? $this->streamFactory->createStreamFromResource($body)
			: $this->streamFactory->createStream((string)$body);

		$response = $this->responseFactory
			->createResponse($nativeResponse->getStatusCode())
			->withBody($stream);

		foreach ($nativeResponse->getHeaders() as $name => $values) {
			$response = $response->withHeader($name, $values);
		}

		return $response;
	}
}
