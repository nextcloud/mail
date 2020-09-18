<?php

declare(strict_types=1);

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
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Service\Attachment\UploadedFile;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\ILogger;
use OCP\IRequest;

class LocalAttachmentsController extends Controller {

	/** @var IAttachmentService */
	private $attachmentService;

	/** @var string */
	private $userId;

	/** @var ILogger */
	private $logger;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IAttachmentService $attachmentService
	 * @param string $UserId
	 */
	public function __construct(string $appName, IRequest $request,
					IAttachmentService $attachmentService, ILogger $logger, $UserId) {
		parent::__construct($appName, $request);
		$this->attachmentService = $attachmentService;
		$this->logger = $logger;
		$this->userId = $UserId;
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @return JSONResponse
	 */
	public function create(): JSONResponse {
		$file = $this->request->getUploadedFile('attachment');

		if (is_null($file)) {
			throw new ClientException('no file attached');
		}

		$uploadedFile = new UploadedFile($file);

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($finfo, $uploadedFile->getTempPath());
		unset($finfo);

		$attachment = $this->attachmentService->addFile($this->userId, $uploadedFile, $mimeType);

		return new JSONResponse($attachment, Http::STATUS_CREATED);
	}
}
