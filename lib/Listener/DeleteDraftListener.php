<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use Horde_Imap_Client;
use Horde_Imap_Client_Exception;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Events\DraftMessageCreatedEvent;
use OCA\Mail\Events\DraftSavedEvent;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\OutboxMessageCreatedEvent;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<Event|DraftSavedEvent|OutboxMessageCreatedEvent|DraftMessageCreatedEvent>
 */
class DeleteDraftListener implements IEventListener {
	/** @var IEventDispatcher */
	private $eventDispatcher;

	public function __construct(
		private readonly \OCA\Mail\IMAP\IMAPClientFactory $imapClientFactory,
		private readonly \OCA\Mail\Db\MailboxMapper $mailboxMapper,
		private readonly \OCA\Mail\IMAP\MessageMapper $messageMapper,
		private readonly \Psr\Log\LoggerInterface $logger,
		IEventDispatcher $eventDispatcher
	) {
		$this->eventDispatcher = $eventDispatcher;
	}

	#[\Override]
	public function handle(Event $event): void {
		if (($event instanceof DraftSavedEvent || $event instanceof OutboxMessageCreatedEvent || $event instanceof DraftMessageCreatedEvent) && $event->getDraft() !== null) {
			$this->deleteDraft($event->getAccount(), $event->getDraft());
		}
	}

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
			throw new DoesNotExistException('No drafts mailbox ID set');
		}
		return $this->mailboxMapper->findById($draftsMailboxId);
	}
}
