<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<Event|MessageFlaggedEvent>
 */
class MoveJunkListener implements IEventListener {
	public function __construct(
		private IMailManager $mailManager,
		private LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!$event instanceof MessageFlaggedEvent || $event->getFlag() !== '$junk') {
			return;
		}

		$account = $event->getAccount();
		$mailAccount = $account->getMailAccount();

		$junkMailboxId = $mailAccount->getJunkMailboxId();
		if ($junkMailboxId === null) {
			return;
		}

		$mailbox = $event->getMailbox();

		if ($event->isSet() && $junkMailboxId !== $mailbox->getId()) {
			try {
				$junkMailbox = $this->mailManager->getMailbox($account->getUserId(), $junkMailboxId);
			} catch (ClientException) {
				$this->logger->debug('junk mailbox set, but junk mailbox does not exist. account_id: {account_id}, junk_mailbox_id: {junk_mailbox_id}', [
					'account_id' => $account->getId(),
					'junk_mailbox_id' => $junkMailboxId,
				]);
				return;
			}

			try {
				$this->mailManager->moveMessage(
					$account,
					$mailbox->getName(),
					$event->getUid(),
					$account,
					$junkMailbox->getName(),
				);
			} catch (ServiceException $e) {
				$this->logger->error('move message to junk mailbox failed. account_id: {account_id}', [
					'exception' => $e,
					'account_id' => $account->getId(),
				]);
			}
		} elseif (!$event->isSet() && $mailbox->getName() !== 'INBOX') {
			try {
				$this->mailManager->moveMessage(
					$account,
					$mailbox->getName(),
					$event->getUid(),
					$account,
					'INBOX',
				);
			} catch (ServiceException $e) {
				$this->logger->error('move message to inbox failed. account_id: {account_id}', [
					'exception' => $e,
					'account_id' => $account->getId(),
				]);
			}
		}
	}
}
