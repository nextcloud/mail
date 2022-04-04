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

use Horde_Imap_Client;
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

class FlagRepliedMessageListener implements IEventListener {

	/** @var IMAPClientFactory */
	private $imapClientFactory;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(IMAPClientFactory $imapClientFactory,
								MailboxMapper $mailboxMapper,
								MessageMapper $mapper,
								LoggerInterface $logger) {
		$this->imapClientFactory = $imapClientFactory;
		$this->mailboxMapper = $mailboxMapper;
		$this->messageMapper = $mapper;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if (!($event instanceof MessageSentEvent) || $event->getRepliedMessageData() === null) {
			return;
		}

		try {
			$mailbox = $this->mailboxMapper->findById(
				$event->getRepliedMessageData()->getMessage()->getMailboxId()
			);
		} catch (DoesNotExistException | ServiceException $e) {
			$this->logger->warning('Could not flag the message in reply to: ' . $e, [
				'exception' => $e,
			]);
			// Not critical -> continue
			return;
		}

		$client = $this->imapClientFactory->getClient($event->getAccount());
		try {
			$this->messageMapper->addFlag(
				$client,
				$mailbox,
				[$event->getRepliedMessageData()->getMessage()->getUid()],
				Horde_Imap_Client::FLAG_ANSWERED
			);
		} catch (Horde_Imap_Client_Exception $e) {
			$this->logger->warning('Could not flag replied message: ' . $e, [
				'exception' => $e,
			]);
		} finally {
			$client->logout();
		}
	}
}
