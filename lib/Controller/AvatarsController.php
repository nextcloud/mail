<?php

/**
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

use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\ISession;

use OCA\Mail\Service\AvatarService;
use OCA\Mail\Http\AvatarDownloadResponse;

class AvatarsController extends Controller {

	/** @var ISession */
	private $session;

	/** @var AvatarService */
	private $avatarService;

	/** @var string */
	private $currentUserId;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param ISession $session
	 * @param AvatarService $avatarService
	 * @param IClientService $clientService
	 * @param string $UserId
	 */
	public function __construct($appName, IRequest $request, ISession $session, AvatarService $avatarService, $userId) {
		parent::__construct($appName, $request);
		$this->session = $session;
		$this->avatarService = $avatarService;
		$this->currentUserId = $userId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $email
	 * @return Response
	 */
	public function url($email) {
		// Get the data from the service
		return $this->avatarService->rewriteUrl($this->avatarService->fetch($email, $this->currentUserId));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $email
	 * @return Response
	 */
	 public function file($email) {
		// Return file (or fail)
		return new AvatarDownloadResponse($this->avatarService->loadFile($email));
	}
}
