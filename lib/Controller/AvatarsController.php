<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jakob Sack <mail@jakobsack.de>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Contracts\IAvatarService;
use OCA\Mail\Http\AvatarDownloadResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;

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
	 * @TrapError
	 *
	 * @param string $email
	 * @return JSONResponse
	 */
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
			$response = new JSONResponse([], Http::STATUS_NOT_FOUND);

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
	 * @TrapError
	 *
	 * @param string $email
	 * @return Response
	 */
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
