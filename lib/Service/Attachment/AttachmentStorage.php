<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Service\Attachment;

use OCA\Mail\Exception\UploadException;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFolder;
use Throwable;

class AttachmentStorage {

	/** @var IAppData */
	private $appData;

	/**
	 * @param IAppData $appData
	 */
	public function __construct(IAppData $appData) {
		$this->appData = $appData;
	}

	/**
	 * @param string $userId
	 * @param IAppData $appData
	 * @return ISimpleFolder
	 * @throws NotPermittedException
	 */
	private function getAttachmentFolder($userId, IAppData $appData) {
		$folderName = implode('_', [
			'mail',
			$userId
		]);

		try {
			return $appData->getFolder($folderName);
		} catch (NotFoundException $ex) {
			return $appData->newFolder($folderName);
		}
	}

	/**
	 * Copy uploaded file content to a app data file
	 *
	 * @param string $userId
	 * @param int $attachmentId
	 * @param UploadedFile $uploadedFile
	 * @throws UploadException
	 */
	public function save($userId, $attachmentId, UploadedFile $uploadedFile) {
		$folder = $this->getAttachmentFolder($userId, $this->appData);

		$file = $folder->newFile($attachmentId);
		$tmpPath = $uploadedFile->getTempPath();
		if (is_null($tmpPath)) {
			throw new UploadException('tmp_path of uploaded file is null');
		}

		try {
			$fileContent = file_get_contents($tmpPath);
		} catch (Throwable $ex) {
			$fileContent = false;
		}

		if ($fileContent === false) {
			throw new UploadException('could not read uploaded file');
		}
		$file->putContent($fileContent);
	}

	public function delete($userId, $attachmentId) {
		throw new \Exception('not implemented');
	}

}
