<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @license AGPL-3.0-or-later
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
		} elseif (!$event->isSet() && 'INBOX' !== $mailbox->getName()) {
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
