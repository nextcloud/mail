<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Controller;

use Exception;
use OCA\Mail\Http\ProxyDownloadResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use function file_get_contents;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ProxyController extends Controller {
	private IURLGenerator $urlGenerator;
	private ISession $session;
	private IClientService $clientService;
	private LoggerInterface $logger;

	public function __construct(string $appName,
		IRequest $request,
		IURLGenerator $urlGenerator,
		ISession $session,
		IClientService $clientService,
		LoggerInterface $logger) {
		parent::__construct($appName, $request);
		$this->request = $request;
		$this->urlGenerator = $urlGenerator;
		$this->session = $session;
		$this->clientService = $clientService;
		$this->logger = $logger;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $src
	 *
	 * @throws \Exception If the URL is not valid.
	 * @return TemplateResponse
	 */
	public function redirect(string $src): TemplateResponse {
		$authorizedRedirect = false;

		if (!str_starts_with($src, 'http://')
			&& !str_starts_with($src, 'https://')
			&& !str_starts_with($src, 'ftp://')) {
			throw new Exception('URL is not valid.', 1);
		}

		// If strict cookies are set it means we come from the same domain so no open redirect
		if ($this->request->passesStrictCookieCheck()) {
			$authorizedRedirect = true;
		}

		$params = [
			'authorizedRedirect' => $authorizedRedirect,
			'url' => $src,
			'urlHost' => parse_url($src, PHP_URL_HOST),
			'mailURL' => $this->urlGenerator->linkToRoute('mail.page.index'),
		];
		return new TemplateResponse($this->appName, 'redirect', $params, 'guest');
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
	 * @return ProxyDownloadResponse
	 */
	#[UserRateLimit(limit: 50, period: 60)]
	public function proxy(string $src): ProxyDownloadResponse {
		// close the session to allow parallel downloads
		$this->session->close();

		// If strict cookies are set it means we come from the same domain so no open redirect
		if (!$this->request->passesStrictCookieCheck()) {
			$content = file_get_contents(__DIR__ . '/../../img/blocked-image.png');
			return new ProxyDownloadResponse($content, $src, 'application/octet-stream');
		}

		$client = $this->clientService->newClient();
		try {
			$response = $client->get($src);
			$content = $response->getBody();
		} catch (ClientExceptionInterface $e) {
			$this->logger->notice('Unable to proxy image', ['exception' => $e]);
			$content = file_get_contents(__DIR__ . '/../../img/blocked-image.png');
		}

		return new ProxyDownloadResponse($content, $src, 'application/octet-stream');
	}
}
