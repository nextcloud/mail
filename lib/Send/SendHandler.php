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

		// Resolve the Sent mailbox before calling the connector
		$sentMailboxId = $account->getMailAccount()->getSentMailboxId();
		if ($sentMailboxId === null) {
			$localMessage->setStatus(LocalMessage::STATUS_NO_SENT_MAILBOX);
			return $localMessage;
		}
		try {
			$sentMailbox = $this->mailboxMapper->findById($sentMailboxId);
		} catch (DoesNotExistException $e) {
			$this->logger->error('Sent mailbox not found', ['exception' => $e]);
			$localMessage->setStatus(LocalMessage::STATUS_NO_SENT_MAILBOX);
			return $localMessage;
		}

		$this->protocolFactory->transmissionConnector($account)->sendMessage($account, $localMessage, $sentMailbox);

		if ($localMessage->getStatus() === LocalMessage::STATUS_RAW
			|| $localMessage->getStatus() === null
			|| $localMessage->getStatus() === LocalMessage::STATUS_PROCESSED) {
			$this->eventDispatcher->dispatchTyped(new MessageSentEvent($account, $localMessage));
			return $this->processNext($account, $localMessage);
		}
		// Something went wrong during the sending
		return $localMessage;
	}
}
