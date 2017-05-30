<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Luc Calaresu <dev@calaresu.com>
 *
 * ownCloud - Mail
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

use OCP\IRequest;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\Attachment\UploadedFile;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;

class LocalAttachmentsController extends Controller {

	/** @var AttachmentService */
	private $service;

	/** @var string */
	private $userId;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param AttachmentService $service,
	 * @param string $UserId
	 */
	public function __construct($appName, IRequest $request,
		AttachmentService $service, $UserId) {
		parent::__construct($appName, $request);
		$this->service = $service;
		$this->userId = $UserId;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JSONResponse
	 */
	public function create() {
		$file = $this->request->getUploadedFile('attachment');

		$uploadedFile = new UploadedFile($file);
		$attachment = $this->service->addFile($this->userId, $uploadedFile);

		return $attachment;
	}

}
