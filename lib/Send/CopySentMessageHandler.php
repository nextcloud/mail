<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Send;

use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\IMAP\MessageMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;

class CopySentMessageHandler extends AHandler {
	public function __construct(
		private MailboxMapper $mailboxMapper,
		private LoggerInterface $logger,
		private MessageMapper $messageMapper,
	) {
	}

	private function isAlreadySavedByServer(
		Horde_Imap_Client_Socket $client,
		Mailbox $mailbox,
		string $rawMessage,
	): bool {
		if (!preg_match('/^Message-ID:\s*(<[^>]+>)/im', $rawMessage, $m)) {
			return false;
		}
		try {
			return $this->messageMapper->existsInMailboxByMessageId($client, $mailbox, $m[1]);
		} catch (Horde_Imap_Client_Exception $e) {
			$this->logger->warning('Could not search for existing sent message, proceeding with APPEND', ['exception' => $e]);
			return false;
		}
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

		$sentMailboxId = $account->getMailAccount()->getSentMailboxId();
		if ($sentMailboxId === null) {
			// We can't write the "sent mailbox" status here bc that would trigger an additional send.
			// Thus, we leave the "imap copy to sent mailbox" status.
			$localMessage->setStatus(LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL);
			$this->logger->warning("No sent mailbox exists, can't save sent message");
			return $localMessage;
		}

		// Save the message in the sent mailbox
		try {
			$sentMailbox = $this->mailboxMapper->findById(
				$sentMailboxId
			);
		} catch (DoesNotExistException $e) {
			// We can't write the "sent mailbox" status here bc that would trigger an additional send.
			// Thus, we leave the "imap copy to sent mailbox" status.
			$localMessage->setStatus(LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL);
			$this->logger->error('Sent mailbox could not be found', [
				'exception' => $e,
			]);

			return $localMessage;
		}

		// Some servers (e.g. Exchange) auto-save sent messages, so skip the APPEND when the
		// message is already present to avoid duplicates in the Sent folder.
		if ($this->isAlreadySavedByServer($client, $sentMailbox, $rawMessage)) {
			$this->logger->debug('Sent message already present in sent mailbox (server auto-saved), skipping APPEND');
			$localMessage->setStatus(LocalMessage::STATUS_PROCESSED);
			return $this->processNext($account, $localMessage, $client);
		}

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
}
