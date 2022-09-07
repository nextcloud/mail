<?php

declare(strict_types=1);
/*
 * *
 *  * Mail App
 *  *
 *  * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *  *
 *  * @author Anna Larch <anna.larch@gmx.net>
 *  *
 *  * This library is free software; you can redistribute it and/or
 *  * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 *  * License as published by the Free Software Foundation; either
 *  * version 3 of the License, or any later version.
 *  *
 *  * This library is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *  *
 *  * You should have received a copy of the GNU Affero General Public
 *  * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *  *
 *
 */

namespace OCA\Mail\Service;

use OC\EventDispatcher\EventDispatcher;
use OCA\Mail\Account;
use OCA\Mail\Contracts\ILocalMailboxService;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Events\DraftMessageDeletedEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\Attachment\AttachmentService;
use Psr\Log\LoggerInterface;

class DraftsService implements ILocalMailboxService {
	private LocalMessageMapper $mapper;
	private LoggerInterface $logger;
	private IMAPClientFactory $clientFactory;
	private EventDispatcher $eventDispatcher;
	private AttachmentService $attachmentService;

	public function __construct(LocalMessageMapper $mapper,
		AttachmentService $attachmentService,
		IMAPClientFactory $clientFactory,
		EventDispatcher $eventDispatcher,
		LoggerInterface $logger) {
		$this->mapper = $mapper;
		$this->logger = $logger;
		$this->clientFactory = $clientFactory;
		$this->eventDispatcher = $eventDispatcher;
		$this->attachmentService = $attachmentService;
	}

	/**
	 * @param array $recipients
	 * @param int $type
	 * @return Recipient[]
	 */
	private static function convertToRecipient(array $recipients, int $type): array {
		return array_map(function ($recipient) use ($type) {
			$r = new Recipient();
			$r->setType($type);
			$r->setLabel($recipient['label'] ?? $recipient['email']);
			$r->setEmail($recipient['email']);
			return $r;
		}, $recipients);
	}


	/**
	 * @param string $userId
	 * @return LocalMessage[]
	 */
	public function getMessages(string $userId): array {
		return $this->mapper->getAllForUser($userId, LocalMessage::TYPE_DRAFT);
	}

	public function getMessage(int $id, string $userId): LocalMessage {
		return $this->mapper->findById($id, $userId, LocalMessage::TYPE_DRAFT);
	}

	/**
	 * @param Account|null $account - null value allowed to correctly extend the Interface
	 */
	public function deleteMessage(string $userId, LocalMessage $message, Account $account = null): void {
		// trigger a delete on IMAP
		if ($message->getUid() !== null && $account !== null) {
			$this->eventDispatcher->dispatchTyped(new DraftMessageDeletedEvent($account, $message));
		}
		$this->attachmentService->deleteLocalMessageAttachments($userId, $message->getId());
		$this->mapper->deleteWithRecipients($message);
	}

	public function sendMessage(LocalMessage $message, Account $account): void {
		if (empty($message->getRecipients())) {
			throw new ClientException('Could not send message as recipient is missing');
		}

		// We have an IMAP draft
		if ($message->getUid() !== null) {
			$this->eventDispatcher->dispatchTyped(new DraftMessageDeletedEvent($account, $message));
			$message->setUid(null);
		}

		$message->setType(LocalMessage::TYPE_OUTGOING);
		$this->mapper->update($message);
	}

	public function saveMessage(Account $account, LocalMessage $message, array $to, array $cc, array $bcc, array $attachments = []): LocalMessage {
		$toRecipients = self::convertToRecipient($to, Recipient::TYPE_TO);
		$ccRecipients = self::convertToRecipient($cc, Recipient::TYPE_CC);
		$bccRecipients = self::convertToRecipient($bcc, Recipient::TYPE_BCC);

		$message = $this->mapper->saveWithRecipients($message, $toRecipients, $ccRecipients, $bccRecipients);

		if (empty($attachments)) {
			$message->setAttachments($attachments);
			return $message;
		}

		$client = $this->clientFactory->getClient($account);
		try {
			$attachmentIds = $this->attachmentService->handleAttachments($account, $attachments, $client);
		} finally {
			$client->logout();
		}

		$message->setAttachments($this->attachmentService->saveLocalMessageAttachments($account->getUserId(), $message->getId(), $attachmentIds));
		return $message;
	}

	public function updateMessage(Account $account, LocalMessage $message, array $to, array $cc, array $bcc, array $attachments = []): LocalMessage {
		// Delete the IMAP draft
		if ($message->getUid() !== null) {
			$this->eventDispatcher->dispatchTyped(new DraftMessageDeletedEvent($account, $message));
			$message->setUid(null);
		}

		$toRecipients = self::convertToRecipient($to, Recipient::TYPE_TO);
		$ccRecipients = self::convertToRecipient($cc, Recipient::TYPE_CC);
		$bccRecipients = self::convertToRecipient($bcc, Recipient::TYPE_BCC);
		$message = $this->mapper->updateWithRecipients($message, $toRecipients, $ccRecipients, $bccRecipients);

		if (empty($attachments)) {
			$message->setAttachments($this->attachmentService->updateLocalMessageAttachments($account->getUserId(), $message, $attachments));
			return $message;
		}

		$client = $this->clientFactory->getClient($account);
		try {
			$attachmentIds = $this->attachmentService->handleAttachments($account, $attachments, $client);
		} finally {
			$client->logout();
		}
		$message->setAttachments($this->attachmentService->updateLocalMessageAttachments($account->getUserId(), $message, $attachmentIds));
		return $message;
	}
}
