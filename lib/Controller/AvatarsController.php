<?php

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

use OC;
use OCA\Mail\Contracts\IAvatarService;
use OCA\Mail\Http\AvatarDownloadResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\Files\IMimeTypeDetector;
use OCP\IRequest;

class AvatarsController extends Controller {

	/** @var IAvatarService */
	private $avatarService;

	/** @var string */
	private $uid;

	/** @var IMimeTypeDetector */
	private $mimeDetector;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IAvatarService $avatarService
	 * @param IMimeTypeDetector $mimeDetector
	 * @param string $UserId
	 */
	public function __construct($appName, IRequest $request, IAvatarService $avatarService, IMimeTypeDetector $mimeDetector, $UserId) {
		parent::__construct($appName, $request);
		$this->avatarService = $avatarService;
		$this->mimeDetector = $mimeDetector;
		$this->uid = $UserId;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $email
	 * @return Response
	 */
	public function url($email) {
		if (is_null($email) || empty($email)) {
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}

		$avatarUrl = $this->avatarService->getAvatarUrl($email, $this->uid);
		if (is_null($avatarUrl)) {
			// No avatar found
			$response = new JSONResponse([], Http::STATUS_NOT_FOUND);

			// Debounce this a bit
			// (cache for one day)
			$response->cacheFor(24 * 60 * 60);

			return $response;
		}

		$response = new JSONResponse([
			'url' => $avatarUrl,
		]);

		// Let the browser cache this for a week
		$response->cacheFor(7 * 24 * 60 * 60);

		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $email
	 * @return Response
	 */
	public function image($email) {
		if (is_null($email) || empty($email)) {
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}

		$imageData = $this->avatarService->getAvatarImage($email, $this->uid);

		if (is_null($imageData)) {
			// This could happen if the cache invalidated meanwhile
			$response = new Response();
			$response->setStatus(Http::STATUS_NOT_FOUND);
			// Clear cache
			$response->cacheFor(0);
			return $response;
		}

		// TODO: limit to known MIME types
		$mime = $this->mimeDetector->detectString($imageData);
		$resp = new AvatarDownloadResponse($imageData);
		$resp->addHeader('Content-Type', $mime);
		return $resp;
	}

}
