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
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\IMAP\MessageMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;

class SaveSentMessageListener implements IEventListener {

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var IMAPClientFactory */
	private $imapClientFactory;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var MailboxSync */
	private $mailboxSync;

	/** @var ILogger */
	private $logger;

	public function __construct(MailboxMapper $mailboxMapper,
								IMAPClientFactory $imapClientFactory,
								MessageMapper $messageMapper,
								MailboxSync $mailboxSync,
								ILogger $logger) {
		$this->mailboxMapper = $mailboxMapper;
		$this->imapClientFactory = $imapClientFactory;
		$this->messageMapper = $messageMapper;
		$this->mailboxSync = $mailboxSync;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if (!($event instanceof MessageSentEvent)) {
			return;
		}

		// Save the message in the sent mailbox
		try {
			$sentMailbox = $this->mailboxMapper->findSpecial($event->getAccount(), 'sent');
		} catch (DoesNotExistException $e) {
			$this->logger->debug('creating sent mailbox');
			$sentMailbox = $this->createSentMailbox($event->getAccount());
			$this->logger->debug('sent mailbox created');
		}

		try {
			$this->messageMapper->save(
				$this->imapClientFactory->getClient($event->getAccount()),
				$sentMailbox,
				$event->getMail()
			);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException('Could not save sent message on IMAP', 0, $e);
		}
	}

	/**
	 * @throws DoesNotExistException
	 * @throws ServiceException
	 */
	private function createSentMailbox(Account $account): Mailbox {
		$client = $this->imapClientFactory->getClient($account);

		try {
			// TODO: localize mailbox name
			$client->createMailbox(
				'Sent',
				[
					'special_use' => [
						\Horde_Imap_Client::SPECIALUSE_SENT,
					],
				]
			);
		} catch (Horde_Imap_Client_Exception $e) {
			// Let's assume this error is caused because the mailbox already exists,
			// caused by concurrent requests or out-of-sync mailbox cache
			$this->logger->logException($e, [
				'message' => 'Could not create sent mailbox: ' . $e->getMessage(),
				'level' => ILogger::WARN,
			]);
		}

		// TODO: find a more elegant solution for updating the mailbox cache
		$this->mailboxSync->sync($account, true);

		return $this->mailboxMapper->findSpecial($account, 'sent');
	}
}
