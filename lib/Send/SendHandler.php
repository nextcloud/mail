<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Send;

use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\Protocol\ProtocolFactory;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;

class SendHandler extends AHandler {
	public function __construct(
		private ProtocolFactory $protocolFactory,
		private IEventDispatcher $eventDispatcher,
		private MailboxMapper $mailboxMapper,
		private LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function process(
		Account $account,
		LocalMessage $localMessage,
	): LocalMessage {
		if ($localMessage->getStatus() === LocalMessage::STATUS_PROCESSED) {
			return $this->processNext($account, $localMessage);
		}

		// A failed copy to Sent was already delivered via SMTP, so keep its status to prevent a resend
		$isSentCopyRetry = $localMessage->getStatus() === LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL;
		$noSentMailboxStatus = $isSentCopyRetry
			? LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL
			: LocalMessage::STATUS_NO_SENT_MAILBOX;

		$sentMailboxId = $account->getMailAccount()->getSentMailboxId();
		if ($sentMailboxId === null) {
			$localMessage->setStatus($noSentMailboxStatus);
			return $localMessage;
		}
		try {
			$sentMailbox = $this->mailboxMapper->findById($sentMailboxId);
		} catch (DoesNotExistException $e) {
			$this->logger->error('Sent mailbox not found', ['exception' => $e]);
			$localMessage->setStatus($noSentMailboxStatus);
			return $localMessage;
		}

		$this->protocolFactory->transmissionConnector($account)->sendMessage($account, $localMessage, $sentMailbox);

		$status = $localMessage->getStatus();
		// The message is delivered even if only the copy to Sent failed, and a retry of that copy was announced before
		if (!$isSentCopyRetry && in_array($status, [LocalMessage::STATUS_RAW, null, LocalMessage::STATUS_PROCESSED, LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL], true)) {
			$this->eventDispatcher->dispatchTyped(new MessageSentEvent($account, $localMessage));
		}
		if (in_array($status, [LocalMessage::STATUS_RAW, null, LocalMessage::STATUS_PROCESSED], true)) {
			return $this->processNext($account, $localMessage);
		}
		// Something went wrong during the sending
		return $localMessage;
	}
}
