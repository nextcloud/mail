<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Listener;

use Horde_Imap_Client_Exception;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<Event|MessageSentEvent>
 */
class SaveSentMessageListener implements IEventListener {
	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var IMAPClientFactory */
	private $imapClientFactory;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(MailboxMapper $mailboxMapper,
								IMAPClientFactory $imapClientFactory,
								MessageMapper $messageMapper,
								LoggerInterface $logger) {
		$this->mailboxMapper = $mailboxMapper;
		$this->imapClientFactory = $imapClientFactory;
		$this->messageMapper = $messageMapper;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if (!($event instanceof MessageSentEvent)) {
			return;
		}

		$sentMailboxId = $event->getAccount()->getMailAccount()->getSentMailboxId();
		if ($sentMailboxId === null) {
			$this->logger->warning("No sent mailbox exists, can't save sent message");
			return;
		}

		// Save the message in the sent mailbox
		try {
			$sentMailbox = $this->mailboxMapper->findById(
				$sentMailboxId
			);
		} catch (DoesNotExistException $e) {
			$this->logger->error("Sent mailbox could not be found", [
				'exception' => $e,
			]);
			return;
		}

		$client = $this->imapClientFactory->getClient($event->getAccount());
		try {
			$this->messageMapper->save(
				$client,
				$sentMailbox,
				$event->getMail()
			);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException('Could not save sent message on IMAP', 0, $e);
		} finally {
			$client->logout();
		}
	}
}
