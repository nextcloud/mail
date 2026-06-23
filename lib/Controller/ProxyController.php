<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Html\ProxyHmacGenerator;
use OCA\Mail\Http\ProxyDownloadResponse;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Service\SvgSanitizer;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\Response;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\LocalServerException;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use function file_get_contents;
use function hash_equals;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ProxyController extends Controller {
	private IURLGenerator $urlGenerator;
	private ISession $session;
	private IClientService $clientService;
	private LoggerInterface $logger;
	private ProxyHmacGenerator $hmacGenerator;
	private MailManager $mailManager;
	private SvgSanitizer $svgSanitizer;
	private ?string $userId;

	public function __construct(string $appName,
		IRequest $request,
		IURLGenerator $urlGenerator,
		ISession $session,
		IClientService $clientService,
		ProxyHmacGenerator $hmacGenerator,
		LoggerInterface $logger,
		MailManager $mailManager,
		SvgSanitizer $svgSanitizer,
		?string $userId) {
		parent::__construct($appName, $request);
		$this->request = $request;
		$this->urlGenerator = $urlGenerator;
		$this->session = $session;
		$this->clientService = $clientService;
		$this->logger = $logger;
		$this->hmacGenerator = $hmacGenerator;
		$this->mailManager = $mailManager;
		$this->svgSanitizer = $svgSanitizer;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @UserRateThrottle(limit=50, period=60)
	 *
	 * @param string $src
	 *
	 * TODO: Cache the proxied content to prevent unnecessary requests from the oC server
	 *       The caching should also already happen in a cronjob so that the sender of the
	 *       mail does not know whether the mail has been opened.
	 *
	 * @return Response|ProxyDownloadResponse
	 */
	#[UserRateLimit(limit: 50, period: 60)]
	public function proxy(string $src, ?int $id, ?string $hmac): Response {
		// close the session to allow parallel downloads
		$this->session->close();

		// If strict cookies are set it means we come from the same domain so no open redirect
		if (!$this->request->passesStrictCookieCheck()) {
			$content = file_get_contents(__DIR__ . '/../../img/blocked-image.png');
			return new ProxyDownloadResponse($content, $src, 'application/octet-stream');
		}

		// HMAC check
		if ($this->userId === null || $id === null || $hmac === null) {
			return new Response(Http::STATUS_BAD_REQUEST);
		}
		try {
			$this->mailManager->getMessage($this->userId, $id);
		} catch (DoesNotExistException $e) {
			return new Response(Http::STATUS_BAD_REQUEST);
		}
		if (!hash_equals($this->hmacGenerator->generate($id, $src), $hmac)) {
			$this->logger->info('Proxied email content blocked due to invalid HMAC');
			return new Response(Http::STATUS_UNAUTHORIZED);
		}

		$client = $this->clientService->newClient();
		try {
			$response = $client->get($src);
			$content = $response->getBody();
		} catch (ClientExceptionInterface $e) {
			$this->logger->notice('Unable to proxy image', ['exception' => $e]);
			$content = file_get_contents(__DIR__ . '/../../img/blocked-image.png');
		} catch (LocalServerException $e) {
			$this->logger->warning('Prevented image proxy access to forbidden URL', [
				'blockedUrl' => $src,
				'exception' => $e,
			]);
			$content = file_get_contents(__DIR__ . '/../../img/blocked-image.png');
		}

		$content = (string)$content;

		// Browsers sniff raster image formats in <img> tags, but they refuse to
		// render SVG unless it is served with the image/svg+xml content type.
		// Detect and sanitise SVG markup so external SVG logos are displayed
		// instead of staying blank. Sanitising also strips any active content in
		// case the response is fetched through a direct (non-<img>) navigation.
		if ($this->svgSanitizer->looksLikeSvg($content)) {
			$sanitized = $this->svgSanitizer->sanitize($content);
			if ($sanitized === '') {
				$content = (string)file_get_contents(__DIR__ . '/../../img/blocked-image.png');
				return new ProxyDownloadResponse($content, $src, 'application/octet-stream');
			}
			return new ProxyDownloadResponse($sanitized, $src, 'image/svg+xml');
		}

		return new ProxyDownloadResponse($content, $src, 'application/octet-stream');
	}
}
