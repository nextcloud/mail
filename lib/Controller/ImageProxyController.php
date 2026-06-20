<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IMimeTypeDetector;
use OCA\Mail\Service\SvgSanitizer;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\LocalServerException;
use OCP\IRequest;
use OCP\Security\IRemoteHostValidator;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use function base64_encode;
use function fclose;
use function feof;
use function fread;
use function in_array;
use function is_resource;
use function ltrim;
use function parse_url;
use function str_starts_with;
use function stripos;
use function strlen;

/**
 * Downloads an external image server-side so it can be embedded into a composed
 * message or a signature as a data: URI.
 *
 * Loading external images directly in the browser is blocked by the app's
 * Content Security Policy, so the user provides a URL, the server fetches it
 * once (with the platform's SSRF protection) and returns the bytes as a data:
 * URI. The image is later turned into an inline CID attachment when the message
 * is sent, see {@see \OCA\Mail\Service\MimeMessage::extractDataUriImages()}.
 */
#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ImageProxyController extends Controller {
	/**
	 * Hard cap on the downloaded image size to avoid exhausting memory and to
	 * keep outgoing messages within sane limits.
	 */
	private const MAX_IMAGE_SIZE = 10 * 1024 * 1024;

	/**
	 * Image types that browsers can render in an <img> tag and that are safe to
	 * embed.
	 */
	private const ALLOWED_MIME_TYPES = [
		'image/png',
		'image/jpeg',
		'image/gif',
		'image/webp',
		'image/bmp',
		'image/svg+xml',
	];

	public function __construct(
		string $appName,
		IRequest $request,
		private IClientService $clientService,
		private IRemoteHostValidator $remoteHostValidator,
		private IMimeTypeDetector $mimeTypeDetector,
		private SvgSanitizer $svgSanitizer,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Fetch an external image and return it as a data: URI.
	 *
	 * @NoAdminRequired
	 * @UserRateThrottle(limit=50, period=60)
	 */
	#[UserRateLimit(limit: 50, period: 60)]
	public function fetch(string $url): JSONResponse {
		$scheme = parse_url($url, PHP_URL_SCHEME);
		if ($scheme !== 'http' && $scheme !== 'https') {
			return new JSONResponse(['message' => 'Invalid URL'], Http::STATUS_BAD_REQUEST);
		}

		// Reject internal/local hosts up front (SSRF). The client throwing a
		// LocalServerException below additionally guards against redirects.
		if (!$this->remoteHostValidator->isValid($url)) {
			return new JSONResponse(['message' => 'Forbidden URL'], Http::STATUS_FORBIDDEN);
		}

		$client = $this->clientService->newClient();
		try {
			$response = $client->get($url, [
				'timeout' => 10,
				'stream' => true,
			]);
		} catch (LocalServerException $e) {
			$this->logger->warning('Blocked image insert from forbidden URL', [
				'exception' => $e,
			]);
			return new JSONResponse(['message' => 'Forbidden URL'], Http::STATUS_FORBIDDEN);
		} catch (ClientExceptionInterface $e) {
			$this->logger->info('Could not fetch image to insert', [
				'exception' => $e,
			]);
			return new JSONResponse(['message' => 'Could not fetch image'], Http::STATUS_BAD_GATEWAY);
		}

		$body = $response->getBody();
		if (!is_resource($body)) {
			return new JSONResponse(['message' => 'Could not fetch image'], Http::STATUS_BAD_GATEWAY);
		}

		// Read the response incrementally to enforce the size limit without
		// buffering arbitrarily large responses in memory.
		$content = '';
		while (!feof($body)) {
			$chunk = fread($body, 8192);
			if ($chunk === false) {
				break;
			}
			$content .= $chunk;
			if (strlen($content) > self::MAX_IMAGE_SIZE) {
				fclose($body);
				return new JSONResponse(['message' => 'Image too large'], Http::STATUS_REQUEST_ENTITY_TOO_LARGE);
			}
		}
		fclose($body);

		// Detect the type from the actual bytes instead of trusting the remote
		// Content-Type header.
		$mimeType = $this->mimeTypeDetector->detectString($content);

		// finfo frequently reports SVG (which is plain XML) as a text/* type, so
		// fall back to sniffing the markup to support SVG logos.
		if ($mimeType !== 'image/svg+xml' && $this->looksLikeSvg($content)) {
			$mimeType = 'image/svg+xml';
		}

		if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
			return new JSONResponse(['message' => 'Unsupported image type'], Http::STATUS_UNSUPPORTED_MEDIA_TYPE);
		}

		if ($mimeType === 'image/svg+xml') {
			$content = $this->svgSanitizer->sanitize($content);
		}

		$dataUri = 'data:' . $mimeType . ';base64,' . base64_encode($content);
		return new JSONResponse(['data' => $dataUri]);
	}

	/**
	 * Heuristically decide whether the given bytes are an SVG document.
	 */
	private function looksLikeSvg(string $content): bool {
		$start = ltrim($content);
		$hasSvgRoot = str_starts_with($start, '<?xml')
			|| str_starts_with($start, '<!--')
			|| stripos($start, '<svg') === 0;
		return $hasSvgRoot && stripos($content, '<svg') !== false;
	}
}
