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
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Service\MailManager;
use Psr\Log\LoggerInterface;

class CopySentMessageHandler extends AHandler {
	public function __construct(
		private MailboxMapper $mailboxMapper,
		private LoggerInterface $logger,
		private MessageMapper $messageMapper,
		private MailManager $mailManager,
	) {
	}

	#[\Override]
	public function process(
		Account $account,
		LocalMessage $localMessage,
		Horde_Imap_Client_Socket $client,
	): LocalMessage {
		if ($localMessage->getStatus() === LocalMessage::STATUS_PROCESSED) {
			return $this->processNext($account, $localMessage, $client);
		}

		$rawMessage = $localMessage->getRaw();
		if ($rawMessage === null) {
			$localMessage->setStatus(LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL);
			return $localMessage;
		}

		$sentMailbox = $this->findOrCreateSentMailbox($account);

		try {
			$this->messageMapper->save(
				$client,
				$sentMailbox,
				$rawMessage,
			);
			$localMessage->setStatus(LocalMessage::STATUS_PROCESSED);
		} catch (Horde_Imap_Client_Exception $e) {
			$localMessage->setStatus(LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL);
			$this->logger->error('Could not copy message to sent mailbox', [
				'exception' => $e,
			]);
			return $localMessage;
		}

		return $this->processNext($account, $localMessage, $client);
	}

	private function findOrCreateSentMailbox(Account $account): Mailbox {
		$sentMailboxId = $account->getMailAccount()->getSentMailboxId();

		if ($sentMailboxId === null) {
			return $this->mailManager->createMailbox(
				$account,
				'Sents',
				[Horde_Imap_Client::SPECIALUSE_SENT]
			);
		}

		return $this->mailboxMapper->findById($sentMailboxId);
	}
}
