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
use OCA\Mail\Db\Message;
use OCA\Mail\Events\DraftMessageCreatedEvent;
use OCA\Mail\Events\DraftSavedEvent;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\OutboxMessageCreatedEvent;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<Event|DraftSavedEvent|OutboxMessageCreatedEvent|DraftMessageCreatedEvent>
 */
class DeleteDraftListener implements IEventListener {
	/** @var IMAPClientFactory */
	private $imapClientFactory;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var LoggerInterface */
	private $logger;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	public function __construct(IMAPClientFactory $imapClientFactory,
								MailboxMapper $mailboxMapper,
								MessageMapper $messageMapper,
								LoggerInterface $logger,
								IEventDispatcher $eventDispatcher) {
		$this->imapClientFactory = $imapClientFactory;
		$this->mailboxMapper = $mailboxMapper;
		$this->messageMapper = $messageMapper;
		$this->logger = $logger;
		$this->eventDispatcher = $eventDispatcher;
	}

	public function handle(Event $event): void {
		if (($event instanceof DraftSavedEvent || $event instanceof OutboxMessageCreatedEvent || $event instanceof DraftMessageCreatedEvent) && $event->getDraft() !== null) {
			$this->deleteDraft($event->getAccount(), $event->getDraft());
		}
	}

	/**
	 * @param Account $account
	 * @param Message $draft
	 */
	private function deleteDraft(Account $account, Message $draft): void {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$draftsMailbox = $this->getDraftsMailbox($account);
		} catch (DoesNotExistException $e) {
			$this->logger->warning("Account has no draft mailbox set, can't delete the draft");
			return;
		} finally {
			$client->logout();
		}

		try {
			$this->messageMapper->addFlag(
				$client,
				$draftsMailbox,
				[$draft->getUid()], // TODO: the UID could be from another mailbox
				Horde_Imap_Client::FLAG_DELETED
			);
		} catch (Horde_Imap_Client_Exception $e) {
			$this->logger->error('Could not flag draft as deleted', [
				'exception' => $e,
			]);
		}

		try {
			$client->expunge($draftsMailbox->getName());
		} catch (Horde_Imap_Client_Exception $e) {
			$this->logger->error('Could not expunge drafts folder', [
				'exception' => $e,
			]);
		}

		$this->eventDispatcher->dispatchTyped(
			new MessageDeletedEvent($account, $draftsMailbox, $draft->getUid())
		);
	}

	/**
	 * @throws DoesNotExistException
	 */
	private function getDraftsMailbox(Account $account): Mailbox {
		$draftsMailboxId = $account->getMailAccount()->getDraftsMailboxId();
		if ($draftsMailboxId === null) {
			throw new DoesNotExistException("No drafts mailbox ID set");
		}
		return $this->mailboxMapper->findById($draftsMailboxId);
	}
}
