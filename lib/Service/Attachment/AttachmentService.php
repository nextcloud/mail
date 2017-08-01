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

namespace OCA\Mail\Service\Attachment;

use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\LocalAttachmentMapper;
use OCA\Mail\Exception\AttachmentNotFoundException;
use OCA\Mail\Exception\UploadException;
use OCP\AppFramework\Db\DoesNotExistException;

class AttachmentService implements IAttachmentService {

	/** @var LocalAttachmentMapper */
	private $mapper;

	/** @var AttachmentStorage */
	private $storage;

	/**
	 * @param LocalAttachmentMapper $mapper
	 * @param AttachmentStorage $storage
	 */
	public function __construct(LocalAttachmentMapper $mapper,
		AttachmentStorage $storage) {
		$this->mapper = $mapper;
		$this->storage = $storage;
	}

	/**
	 * @param string $userId
	 * @param UploadedFile $file
	 * @return LocalAttachment
	 * @throws UploadException
	 */
	public function addFile($userId, UploadedFile $file) {
		$attachment = new LocalAttachment();
		$attachment->setUserId($userId);
		$attachment->setFileName($file->getFileName());

		$persisted = $this->mapper->insert($attachment);
		try {
			$this->storage->save($userId, $persisted->id, $file);
		} catch (UploadException $ex) {
			// Clean-up
			$this->mapper->delete($persisted);
			throw $ex;
		}

		return $attachment;
	}

	/**
	 * @param string $userId
	 * @param array $id
	 * @return array of LocalAttachment and ISimpleFile
	 */
	public function getAttachment($userId, $id) {
		try {
			$attachment = $this->mapper->find($userId, $id);
			$file = $this->storage->retrieve($userId, $id);
			return [$attachment, $file];
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
		$this->storage->delete($userId, $id);
	}

}
