<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use Generator;
use Horde_Mail_Rfc822_Address;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\ConfigLexicon;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\Discovery\DiscoveryService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\StreamTraversableResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\Security\IRemoteHostValidator;
use function is_string;
use function json_encode;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class DiscoveryController extends Controller {
	public function __construct(
		IRequest $request,
		private DiscoveryService $discoveryService,
		private IRemoteHostValidator $hostValidator,
		private IAppConfig $appConfig,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * Discover the mail server configurations of an address
	 *
	 * Streams the progress as newline delimited JSON, framed by a start and
	 * an end control event. A missing end event means discovery was aborted.
	 */
	#[NoAdminRequired]
	#[TrapError]
	#[UserRateLimit(limit: 10, period: 600)]
	public function discover(string $email): JsonResponse|StreamTraversableResponse {
		if (!$this->appConfig->getValueBool(Application::APP_ID, ConfigLexicon::ALLOW_NEW_MAIL_ACCOUNTS, true)) {
			return JsonResponse::fail('Creating accounts is disabled', Http::STATUS_FORBIDDEN);
		}
		$address = new Horde_Mail_Rfc822_Address($email);
		if (!$address->valid || !is_string($address->host) || !$this->hostValidator->isValid($address->host)) {
			return JsonResponse::fail('Invalid email address', Http::STATUS_UNPROCESSABLE_ENTITY);
		}

		$events = $this->discoveryService->discover($address);
		$stream = (static function () use ($events): Generator {
			yield json_encode(['type' => 'control', 'disposition' => 'start'], JSON_THROW_ON_ERROR) . "\n";
			foreach ($events as $event) {
				yield json_encode($event, JSON_THROW_ON_ERROR) . "\n";
			}
			yield json_encode(['type' => 'control', 'disposition' => 'end'], JSON_THROW_ON_ERROR) . "\n";
		})();

		return new StreamTraversableResponse($stream, Http::STATUS_OK, [
			'Content-Type' => 'application/x-ndjson',
			'X-Accel-Buffering' => 'no',
		]);
	}
}
