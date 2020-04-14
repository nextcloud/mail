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
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Events\BeforeMessageDeletedEvent;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MailboxSync;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;

class TrashMailboxCreatorListener implements IEventListener {

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var IMAPClientFactory */
	private $imapClientFactory;

	/** @var MailboxSync */
	private $mailboxSync;

	/** @var ILogger */
	private $logger;

	public function __construct(MailboxMapper $mailboxMapper,
								IMAPClientFactory $imapClientFactory,
								MailboxSync $mailboxSync,
								ILogger $logger) {
		$this->mailboxMapper = $mailboxMapper;
		$this->imapClientFactory = $imapClientFactory;
		$this->mailboxSync = $mailboxSync;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeMessageDeletedEvent)) {
			return;
		}

		try {
			$this->mailboxMapper->findSpecial(
				$event->getAccount(),
				'trash'
			);
		} catch (DoesNotExistException $e) {
			$this->logger->debug("Creating trash mailbox");
			$this->createTrash($event->getAccount());
			$this->logger->debug("Trash mailbox created");
		}
	}

	private function createTrash(Account $account): void {
		$client = $this->imapClientFactory->getClient($account);

		try {
			// TODO: localize mailbox name
			$client->createMailbox(
				'Trash',
				[
					'special_use' => [
						\Horde_Imap_Client::SPECIALUSE_TRASH,
					],
				]
			);

			// TODO: find a more elegant solution for updating the mailbox cache
			$this->mailboxSync->sync($account, true);
		} catch (Horde_Imap_Client_Exception $e) {
			$this->logger->logException($e, [
				'message' => 'Could not creat trash mailbox',
			]);
		}
	}
}
