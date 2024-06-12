<?php

declare(strict_types=1);
/**
 * @copyright 2024 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 * @author 2024 Richard Steinmetz <richard@steinmetz.cloud>
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
 */

namespace OCA\Mail\Send;

use Horde_Imap_Client_Exception;
use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;

class CopySentMessageHandler extends AHandler {
	public function __construct(private IMAPClientFactory $imapClientFactory,
		private MailboxMapper $mailboxMapper,
		private LoggerInterface $logger,
		private MessageMapper $messageMapper,
	) {
	}
	public function process(Account $account, LocalMessage $localMessage): LocalMessage {
		if ($localMessage->getStatus() === LocalMessage::STATUS_PROCESSED) {
			return $this->processNext($account, $localMessage);
		}

		$rawMesage = $localMessage->getRaw();
		if ($rawMesage === null) {
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

		$client = $this->imapClientFactory->getClient($account);
		try {
			$this->messageMapper->save(
				$client,
				$sentMailbox,
				$rawMesage,
			);
			$localMessage->setStatus(LocalMessage::STATUS_PROCESSED);
		} catch (Horde_Imap_Client_Exception $e) {
			$localMessage->setStatus(LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL);
			$this->logger->error('Could not copy message to sent mailbox', [
				'exception' => $e,
			]);
			return $localMessage;
		} finally {
			$client->logout();
		}

		return $this->processNext($account, $localMessage);
	}
}
