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

namespace OCA\Mail\Service\Attachment;

use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\LocalAttachmentMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;

class AttachmentService {

	/** @var LocalAttachmentMapper */
	private $mapper;

	/**
	 * @param LocalAttachmentMapper $mapper
	 */
	public function __construct(LocalAttachmentMapper $mapper) {
		$this->mapper = $mapper;
	}

	/**
	 * @param string $userId
	 * @param UploadedFile $file
	 * @return LocalAttachment
	 */
	public function addFile($userId, UploadedFile $file) {
		$attachment = new LocalAttachment();
		$attachment->setUserId($userId);
		$attachment->setFileName($file->getFileName());
		$attachment->setFilePath($file->getPath());

		$this->mapper->insert($attachment);

		return $attachment;
	}

	/**
	 * @param string $userId
	 * @param int $id
	 */
	public function getAttachment($userId, $id) {
		try {
			return $this->mapper->find($userId, $id);
		} catch (DoesNotExistException $ex) {
			throw new AttachmentNotFoundException();
		}
	}

	/**
	 * @param string $userId
	 * @param int $id
	 */
	public function deleteAttachment($userId, $id) {
		try {
			$attachment = $this->mapper->find($userId, $id);
			$this->mapper->delete($attachment);
		} catch (DoesNotExistException $ex) {
			// Nothing to do then
		}
	}

}
