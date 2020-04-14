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
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Events\DraftSavedEvent;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\IMAP\MessageMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;

class DeleteDraftListener implements IEventListener {

	/** @var IMAPClientFactory */
	private $imapClientFactory;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var MailboxSync */
	private $mailboxSync;

	/** @var ILogger */
	private $logger;

	public function __construct(IMAPClientFactory $imapClientFactory,
								MailboxMapper $mailboxMapper,
								MessageMapper $messageMapper,
								MailboxSync $mailboxSync,
								ILogger $logger) {
		$this->imapClientFactory = $imapClientFactory;
		$this->mailboxMapper = $mailboxMapper;
		$this->messageMapper = $messageMapper;
		$this->mailboxSync = $mailboxSync;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if ($event instanceof DraftSavedEvent && $event->getDraftUid() !== null) {
			$this->deleteDraft($event->getAccount(), $event->getDraftUid());
		} elseif ($event instanceof MessageSentEvent && $event->getDraftUid() !== null) {
			$this->deleteDraft($event->getAccount(), $event->getDraftUid());
		}
	}

	/**
	 * @param DraftSavedEvent $event
	 */
	private function deleteDraft(Account $account, int $draftUid): void {
		$client = $this->imapClientFactory->getClient($account);
		$draftsMailbox = $this->getDraftsMailbox($account);

		try {
			$this->messageMapper->addFlag(
				$client,
				$draftsMailbox,
				$draftUid,
				Horde_Imap_Client::FLAG_DELETED
			);
		} catch (Horde_Imap_Client_Exception $e) {
			$this->logger->logException($e, [
				'message' => 'Could not flag draft as deleted'
			]);
		}

		try {
			$client->expunge($draftsMailbox->getName());
		} catch (Horde_Imap_Client_Exception $e) {
			$this->logger->logException($e, [
				'message' => 'Could not expunge drafts folder'
			]);
		}
	}

	private function getDraftsMailbox(Account $account): Mailbox {
		try {
			return $this->mailboxMapper->findSpecial($account, 'drafts');
		} catch (DoesNotExistException $e) {
			$this->logger->debug('Creating drafts mailbox');
			$this->createDraftsMailbox($account);
			$this->logger->debug('Drafts mailbox created');
		}

		return $this->mailboxMapper->findSpecial($account, 'drafts');
	}

	private function createDraftsMailbox(Account $account): void {
		$client = $this->imapClientFactory->getClient($account);

		try {
			// TODO: localize mailbox name
			$client->createMailbox(
				'Drafts',
				[
					'special_use' => [
						\Horde_Imap_Client::SPECIALUSE_DRAFTS,
					],
				]
			);
		} catch (Horde_Imap_Client_Exception $e) {
			$this->logger->logException($e, [
				'message' => 'Could not create drafts mailbox',
			]);
		}

		// TODO: find a more elegant solution for updating the mailbox cache
		$this->mailboxSync->sync($account, true);
	}
}
