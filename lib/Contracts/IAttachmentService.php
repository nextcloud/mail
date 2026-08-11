<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Contracts;

use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Exception\AttachmentNotFoundException;
use OCA\Mail\Service\Attachment\UploadedFile;
use OCP\Files\SimpleFS\ISimpleFile;

interface IAttachmentService {
	/**
	 * Save an uploaded file
	 */
	public function addFile(string $userId, UploadedFile $file): LocalAttachment;

	/**
	 * Try to get an attachment by id
	 *
	 * @return array{0: LocalAttachment, 1: ISimpleFile}
	 * @throws AttachmentNotFoundException
	 */
	public function getAttachment(string $userId, int $id): array;

	/**
	 * Delete an attachment if it exists
	 *
	 * @param string $userId
	 * @param int $id
	 */
	public function deleteAttachment(string $userId, int $id);

}
