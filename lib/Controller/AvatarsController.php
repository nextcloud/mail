<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Contracts\IAvatarService;
use OCA\Mail\Http\AvatarDownloadResponse;
use OCA\Mail\Http\TrapError;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class AvatarsController extends Controller {
	private IAvatarService $avatarService;
	private string $uid;

	public function __construct(string $appName,
		IRequest $request,
		IAvatarService $avatarService,
		string $UserId) {
		parent::__construct($appName, $request);

		$this->avatarService = $avatarService;
		$this->uid = $UserId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $email
	 * @return JSONResponse
	 */
	#[TrapError]
	public function url(string $email): JSONResponse {
		if (empty($email)) {
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}

		$avatar = $this->avatarService->getAvatar($email, $this->uid);
		if (is_null($avatar)) {
			// No avatar found
			$response = new JSONResponse([], Http::STATUS_NO_CONTENT);

			// Debounce this a bit
			// (cache for one day)
			$response->cacheFor(24 * 60 * 60, false, true);

			return $response;
		}

		$response = new JSONResponse($avatar);

		// Let the browser cache this for a week
		$response->cacheFor(7 * 24 * 60 * 60, false, true);

		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $email
	 * @return Response
	 */
	#[TrapError]
	public function image(string $email): Response {
		if (empty($email)) {
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}

		$imageData = $this->avatarService->getAvatarImage($email, $this->uid);
		[$avatar, $image] = $imageData;

		if (is_null($imageData) || !$avatar->isExternal()) {
			// This could happen if the cache invalidated meanwhile
			return $this->noAvatarFoundResponse();
		}

		$resp = new AvatarDownloadResponse($image);
		$resp->addHeader('Content-Type', $avatar->getMime());

		// Let the browser cache this for a week
		$resp->cacheFor(7 * 24 * 60 * 60, false, true);

		return $resp;
	}

	private function noAvatarFoundResponse(): Response {
		$response = new Response();
		$response->setStatus(Http::STATUS_NOT_FOUND);
		// Clear cache
		$response->cacheFor(0);
		return $response;
	}
}
