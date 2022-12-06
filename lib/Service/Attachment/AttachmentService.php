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

namespace OCA\Mail\Service\Attachment;

use finfo;
use InvalidArgumentException;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\LocalAttachmentMapper;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Exception\AttachmentNotFoundException;
use OCA\Mail\Exception\UploadException;
use OCA\Mail\IMAP\MessageMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Psr\Log\LoggerInterface;

class AttachmentService implements IAttachmentService {
	/** @var LocalAttachmentMapper */
	private $mapper;

	/** @var AttachmentStorage */
	private $storage;
	/**
	 * @var IMailManager
	 */
	private $mailManager;
	/**
	 * @var MessageMapper
	 */
	private $messageMapper;

	/**
	 * @var Folder
	 */
	private $userFolder;
	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @param Folder $userFolder
	 */
	public function __construct($userFolder,
								LocalAttachmentMapper $mapper,
								AttachmentStorage $storage,
								IMailManager $mailManager,
								MessageMapper $imapMessageMapper,
								LoggerInterface $logger) {
		$this->mapper = $mapper;
		$this->storage = $storage;
		$this->mailManager = $mailManager;
		$this->messageMapper = $imapMessageMapper;
		$this->userFolder = $userFolder;
		$this->logger = $logger;
	}

	/**
	 * @param string $userId
	 * @param UploadedFile $file
	 * @return LocalAttachment
	 */
	public function addFile(string $userId, UploadedFile $file): LocalAttachment {
		$attachment = new LocalAttachment();
		$attachment->setUserId($userId);
		$attachment->setFileName($file->getFileName());
		$attachment->setMimeType($file->getMimeType());

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
	 * @param UploadedFile $file
	 * @return LocalAttachment
	 */
	public function addFileFromString(string $userId, string $name, string $mime, string $fileContents): LocalAttachment {
		$attachment = new LocalAttachment();
		$attachment->setUserId($userId);
		$attachment->setFileName($name);
		$attachment->setMimeType($mime);

		$persisted = $this->mapper->insert($attachment);
		try {
			$this->storage->saveContent($userId, $persisted->id, $fileContents);
		} catch (NotFoundException|NotPermittedException $e) {
			// Clean-up
			$this->mapper->delete($persisted);
			throw new UploadException($e->getMessage(), $e->getCode(), $e);
		}

		return $attachment;
	}

	/**
	 * @param string $userId
	 * @param int $id
	 *
	 * @return array of LocalAttachment and ISimpleFile
	 *
	 * @throws AttachmentNotFoundException
	 */
	public function getAttachment(string $userId, int $id): array {
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
	 *
	 * @return void
	 */
	public function deleteAttachment(string $userId, int $id) {
		try {
			$attachment = $this->mapper->find($userId, $id);
			$this->mapper->delete($attachment);
		} catch (DoesNotExistException $ex) {
			// Nothing to do then
		}
		$this->storage->delete($userId, $id);
	}

	public function deleteLocalMessageAttachments(string $userId, int $localMessageId): void {
		$attachments = $this->mapper->findByLocalMessageId($userId, $localMessageId);
		// delete db entries
		$this->mapper->deleteForLocalMessage($userId, $localMessageId);
		// delete storage
		foreach ($attachments as $attachment) {
			$this->storage->delete($userId, $attachment->getId());
		}
	}

	public function deleteLocalMessageAttachmentsById(string $userId, int $localMessageId, array $attachmentIds): void {
		$attachments = $this->mapper->findByIds($userId, $attachmentIds);
		// delete storage
		foreach ($attachments as $attachment) {
			$this->mapper->delete($attachment);
			$this->storage->delete($userId, $attachment->getId());
		}
	}

	/**
	 * @param int[] $attachmentIds
	 * @return LocalAttachment[]
	 */
	public function saveLocalMessageAttachments(string $userId, int $messageId, array $attachmentIds): array {
		if (empty($attachmentIds)) {
			return [];
		}
		$this->mapper->saveLocalMessageAttachments($userId, $messageId, $attachmentIds);
		return $this->mapper->findByLocalMessageId($userId, $messageId);
	}

	/**
	 * @return LocalAttachment[]
	 */
	public function updateLocalMessageAttachments(string $userId, LocalMessage $message, array $newAttachmentIds): array {
		// no attachments any more. Delete any old ones and we're done
		if (empty($newAttachmentIds)) {
			$this->deleteLocalMessageAttachments($userId, $message->getId());
			return [];
		}

		// no need to diff, no old attachments
		if (empty($message->getAttachments())) {
			$this->mapper->saveLocalMessageAttachments($userId, $message->getId(), $newAttachmentIds);
			return $this->mapper->findByLocalMessageId($userId, $message->getId());
		}

		$oldAttachmentIds = array_map(static function ($attachment) {
			return $attachment->getId();
		}, $message->getAttachments());

		$add = array_diff($newAttachmentIds, $oldAttachmentIds);
		if (!empty($add)) {
			$this->mapper->saveLocalMessageAttachments($userId, $message->getId(), $add);
		}

		$delete = array_diff($oldAttachmentIds, $newAttachmentIds);
		if (!empty($delete)) {
			$this->deleteLocalMessageAttachmentsById($userId, $message->getId(), $delete);
		}

		return $this->mapper->findByLocalMessageId($userId, $message->getId());
	}


	/**
	 * @param array $attachments
	 * @return int[]
	 */
	public function handleAttachments(Account $account, array $attachments, \Horde_Imap_Client_Socket $client): array {
		$attachmentIds = [];

		if (empty($attachments)) {
			return $attachmentIds;
		}

		foreach ($attachments as $attachment) {
			if (!isset($attachment['type'])) {
				throw new InvalidArgumentException('Attachment does not have a type');
			}

			if ($attachment['type'] === 'local' && isset($attachment['id'])) {
				// attachment already exists, only return the id
				$attachmentIds[] = (int)$attachment['id'];
				continue;
			}
			if ($attachment['type'] === 'message' || $attachment['type'] === 'message/rfc822') {
				// Adds another message as attachment
				$attachmentIds[] = $this->handleForwardedMessageAttachment($account, $attachment, $client);
				continue;
			}
			if ($attachment['type'] === 'message-attachment') {
				// Adds an attachment from another email (use case is, eg., a mail forward)
				$attachmentIds[] = $this->handleForwardedAttachment($account, $attachment, $client);
				continue;
			}

			$attachmentIds[] = $this->handleCloudAttachment($account, $attachment);
		}
		return array_values(array_filter($attachmentIds));
	}

	/**
	 * Add a message as attachment
	 *
	 * @param Account $account
	 * @param mixed[] $attachment
	 * @param \Horde_Imap_Client_Socket $client
	 * @return int|null
	 */
	private function handleForwardedMessageAttachment(Account $account, array $attachment, \Horde_Imap_Client_Socket $client): ?int {
		$attachmentMessage = $this->mailManager->getMessage($account->getUserId(), (int)$attachment['id']);
		$mailbox = $this->mailManager->getMailbox($account->getUserId(), $attachmentMessage->getMailboxId());
		$fullText = $this->messageMapper->getFullText(
			$client,
			$mailbox->getName(),
			$attachmentMessage->getUid()
		);

		// detect mime type
		$mime = 'application/octet-stream';
		if (extension_loaded('fileinfo')) {
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$detectedMime = $finfo->buffer($fullText);
			if ($detectedMime !== false) {
				$mime = $detectedMime;
			}
		}

		try {
			$localAttachment = $this->addFileFromString($account->getUserId(), $attachment['fileName'] ?? $attachmentMessage->getSubject() . '.eml', $mime, $fullText);
		} catch (UploadException $e) {
			$this->logger->error('Could not create attachment', ['exception' => $e]);
			return null;
		}
		return $localAttachment->getId();
	}

	/**
	 * Adds an emails attachments
	 *
	 * @param Account $account
	 * @param mixed[] $attachment
	 * @param \Horde_Imap_Client_Socket $client
	 * @return int
	 * @throws DoesNotExistException
	 */
	private function handleForwardedAttachment(Account $account, array $attachment, \Horde_Imap_Client_Socket $client): ?int {
		$mailbox = $this->mailManager->getMailbox($account->getUserId(), $attachment['mailboxId']);

		$attachments = $this->messageMapper->getRawAttachments(
			$client,
			$mailbox->getName(),
			(int)$attachment['uid'],
			[
				$attachment['id'] ?? []
			]
		);

		if (empty($attachments)) {
			return null;
		}

		// detect mime type
		$mime = 'application/octet-stream';
		if (extension_loaded('fileinfo')) {
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$detectedMime = $finfo->buffer($attachments[0]);
			if ($detectedMime !== false) {
				$mime = $detectedMime;
			}
		}

		try {
			$localAttachment = $this->addFileFromString($account->getUserId(), $attachment['fileName'], $mime, $attachments[0]);
		} catch (UploadException $e) {
			$this->logger->error('Could not create attachment', ['exception' => $e]);
			return null;
		}
		return $localAttachment->getId();
	}

	/**
	 * @param Account $account
	 * @param array $attachment
	 * @return int|null
	 */
	private function handleCloudAttachment(Account $account, array $attachment): ?int {
		if (!isset($attachment['fileName'])) {
			return null;
		}

		$fileName = $attachment['fileName'];
		if (!$this->userFolder->nodeExists($fileName)) {
			return null;
		}

		$file = $this->userFolder->get($fileName);
		if (!$file instanceof File) {
			return null;
		}

		try {
			$localAttachment = $this->addFileFromString($account->getUserId(), $file->getName(), $file->getMimeType(), $file->getContent());
		} catch (UploadException $e) {
			$this->logger->error('Could not create attachment', ['exception' => $e]);
			return null;
		}
		return $localAttachment->getId();
	}
}
