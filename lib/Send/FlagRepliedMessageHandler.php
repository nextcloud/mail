<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Send;

use Horde_Imap_Client;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper as DbMessageMapper;
use OCA\Mail\IMAP\MessageMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;

class FlagRepliedMessageHandler extends AHandler {
	public function __construct(
		private MailboxMapper $mailboxMapper,
		private LoggerInterface $logger,
		private MessageMapper $messageMapper,
		private DbMessageMapper $dbMessageMapper,
	) {
	}

	#[\Override]
	public function process(
		Account $account,
		LocalMessage $localMessage,
		Horde_Imap_Client_Socket $client,
	): LocalMessage {
		if ($localMessage->getStatus() !== LocalMessage::STATUS_PROCESSED) {
			return $localMessage;
		}

		if ($localMessage->getInReplyToMessageId() === null) {
			return $this->processNext($account, $localMessage, $client);
		}

		$messages = $this->dbMessageMapper->findByMessageId($account, $localMessage->getInReplyToMessageId());
		if ($messages === []) {
			return $this->processNext($account, $localMessage, $client);
		}

		foreach ($messages as $message) {
			try {
				$mailbox = $this->mailboxMapper->findById($message->getMailboxId());
				//ignore read-only mailboxes
				if ($mailbox->getMyAcls() !== null && !strpos($mailbox->getMyAcls(), 'w')) {
					continue;
				}
				// ignore drafts and sent
				if ($mailbox->isSpecialUse('sent') || $mailbox->isSpecialUse('drafts')) {
					continue;
				}
				// Mark all other mailboxes that contain the message with the same imap message id as replied
				$this->messageMapper->addFlag(
					$client,
					$mailbox,
					[$message->getUid()],
					Horde_Imap_Client::FLAG_ANSWERED
				);
				$message->setFlagAnswered(true);
				$this->dbMessageMapper->update($message);
			} catch (DoesNotExistException|Horde_Imap_Client_Exception $e) {
				$this->logger->warning('Could not flag replied message: ' . $e, [
					'exception' => $e,
				]);
			}
		}

		return $this->processNext($account, $localMessage, $client);
	}
}
