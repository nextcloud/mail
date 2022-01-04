<?php

declare(strict_types=1);

/**
 * Mail App
 *
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\Contracts\ILocalMailbox;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\LocalAttachmentMapper;
use OCA\Mail\Db\LocalMailboxMessage;
use OCA\Mail\Db\LocalMailboxMessageMapper;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Db\RecipientMapper;
use OCA\Mail\Exception\ServiceException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;

class OutboxService implements ILocalMailbox {

	/** @var IMailTransmission */
	private $transmission;

	/** @var LoggerInterface */
	private $logger;

	/** @var LocalMailboxMessageMapper */
	private $mapper;

	/** @var LocalAttachmentMapper */
	private $attachmentMapper;

	/** @var RecipientMapper */
	private $recipientMapper;

	public function __construct(IMailTransmission $transmission,
								LoggerInterface $logger,
								LocalMailboxMessageMapper $mapper,
								LocalAttachmentMapper $attachmentMapper,
								RecipientMapper $recipientMapper) {
		$this->transmission = $transmission;
		$this->logger = $logger;
		$this->mapper = $mapper;
		$this->attachmentMapper = $attachmentMapper;
		$this->recipientMapper = $recipientMapper;
	}

	/**
	 * @throws ServiceException
	 */
	public function getMessages(string $userId): array {
		try {
			$messages = $this->mapper->getAllForUser($userId);

			// Attach related data
			return array_map(function (LocalMailboxMessage $message) use ($userId) {
				$row = $message->jsonSerialize();
				$row['attachments'] = $this->attachmentMapper->findForLocalMailbox($message->getId(), $userId);
				$row['recipients'] = $this->recipientMapper->findRecipients($message->getId(), Recipient::TYPE_OUTBOX);
				return $row;
			}, $messages);
		} catch (Exception $e) {
			throw new ServiceException("Could not get messages for user $userId", 0, $e);
		}
	}

	/**
	 * @throws ServiceException
	 */
	public function getMessage(int $id): LocalMailboxMessage {
		try {
			return $this->mapper->find($id);
		} catch (DoesNotExistException|MultipleObjectsReturnedException|Exception $e) {
			throw new ServiceException('Could not fetch any messages', 400);
		}
	}

	/**
	 * @throws ServiceException
	 */
	public function deleteMessage(LocalMailboxMessage $message, string $userId): void {
		// also delete all related entries in the recipients and attachments table!
		try {
			$this->mapper->deleteWithRelated($message, $userId);
		} catch (Exception $e) {
			throw new ServiceException('Could not delete message' . $e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @throws ServiceException
	 */
	public function sendMessage(LocalMailboxMessage $message, Account $account): void {
		try {
			$related = $this->mapper->getRelatedData($message->getId(), $account->getUserId());
			$this->transmission->sendLocalMessage($account, $message, $related['recipients'], $related['attachments']);
			$this->mapper->deleteWithRelated($message, $account->getUserId());
		} catch (Exception $e) {
			throw new ServiceException('Could not send message', 0, $e);
		}
	}

	/**
	 * @throws ServiceException
	 */
	public function saveMessage(LocalMailboxMessage $message, array $recipients, array $attachmentIds = []): LocalMailboxMessage {
		try {
			$this->mapper->saveWithRelatedData($message, $recipients, $attachmentIds);
		} catch (Exception $e) {
			throw new ServiceException('Could not save message', 400, $e);
		}
		return $message;
	}
}
