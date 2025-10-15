<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\Attachment;

use OCA\Mail\Exception\AttachmentNotFoundException;
use OCA\Mail\Exception\UploadException;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use Throwable;

class AttachmentStorage {
	/** @var IAppData */
	private $appData;

	public function __construct(IAppData $appData) {
		$this->appData = $appData;
	}

	/**
	 * @param string $userId
	 * @return ISimpleFolder
	 * @throws NotPermittedException
	 */
	private function getAttachmentFolder($userId): ISimpleFolder {
		$folderName = implode('_', [
			'mail',
			$userId
		]);

		try {
			return $this->appData->getFolder($folderName);
		} catch (NotFoundException $ex) {
			return $this->appData->newFolder($folderName);
		}
	}

	/**
	 * Copy uploaded file content to a app data file
	 *
	 * @param string $userId
	 * @param int $attachmentId
	 * @param UploadedFile $uploadedFile
	 *
	 * @throws UploadException
	 *
	 * @return void
	 */
	public function save(string $userId, int $attachmentId, UploadedFile $uploadedFile): void {
		$folder = $this->getAttachmentFolder($userId);

		$file = $folder->newFile((string)$attachmentId);
		$tmpPath = $uploadedFile->getTempPath();
		if ($tmpPath === null) {
			throw new UploadException('tmp_name of uploaded file is null');
		}

		try {
			$fileContent = @file_get_contents($tmpPath);
		} catch (Throwable $ex) {
			$fileContent = false;
		}

		if ($fileContent === false) {
			throw new UploadException('could not read uploaded file');
		}
		$file->putContent($fileContent);
	}

	/**
	 * Copy uploaded file content to a app data file
	 *
	 * @param string $userId
	 * @param int $attachmentId
	 *
	 * @return void
	 * @throws NotFoundException|NotPermittedException
	 */
	public function saveContent(string $userId, int $attachmentId, string $fileContent): void {
		$folder = $this->getAttachmentFolder($userId);
		$file = $folder->newFile((string)$attachmentId);
		$file->putContent($fileContent);
	}




	/**
	 * @param string $userId
	 * @param int $attachmentId
	 * @return ISimpleFile
	 * @throws AttachmentNotFoundException
	 */
	public function retrieve(string $userId, int $attachmentId) {
		$folder = $this->getAttachmentFolder($userId);

		try {
			return $folder->getFile((string)$attachmentId);
		} catch (NotFoundException $ex) {
			throw new AttachmentNotFoundException();
		}
	}

	public function delete(string $userId, int $attachmentId): void {
		$folder = $this->getAttachmentFolder($userId);
		try {
			$file = $folder->getFile((string)$attachmentId);
		} catch (NotFoundException $e) {
			return;
		}
		$file->delete();
	}
}
