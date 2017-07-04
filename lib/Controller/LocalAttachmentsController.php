<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Luc Calaresu <dev@calaresu.com>
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

use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\Attachment\UploadedFile;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class LocalAttachmentsController extends Controller {

	/** @var AttachmentService */
	private $attachmentService;

	/** @var string */
	private $userId;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IAttachmentService $attachmentService
	 * @param string $UserId
	 */
	public function __construct($appName, IRequest $request,
		IAttachmentService $attachmentService, $UserId) {
		parent::__construct($appName, $request);
		$this->attachmentService = $attachmentService;
		$this->userId = $UserId;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JSONResponse
	 */
	public function create() {
		$file = $this->request->getUploadedFile('attachment');

		if (is_null($file)) {
			return new JSONResponse(null, Http::STATUS_BAD_REQUEST);
		}

		$uploadedFile = new UploadedFile($file);
		$attachment = $this->attachmentService->addFile($this->userId, $uploadedFile);

		return new JSONResponse($attachment, Http::STATUS_CREATED);
	}

}
