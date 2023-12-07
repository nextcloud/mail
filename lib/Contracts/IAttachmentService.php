<?php

declare(strict_types=1);

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

namespace OCA\Mail\Contracts;

use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Exception\AttachmentNotFoundException;
use OCA\Mail\Service\Attachment\UploadedFile;

interface IAttachmentService {
	/**
	 * Save an uploaded file
	 */
	public function addFile(string $userId, UploadedFile $file): LocalAttachment;

	/**
	 * Try to get an attachment by id
	 *
	 * @throws AttachmentNotFoundException
	 * @return array of LocalAttachment and ISimpleFile
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
