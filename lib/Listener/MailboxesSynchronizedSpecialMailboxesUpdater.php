<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Events\MailboxesSynchronizedEvent;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use function array_combine;
use function array_key_exists;
use function array_map;
use function in_array;
use function json_decode;
use function strtolower;

/**
 * @template-implements IEventListener<Event|MailboxesSynchronizedEvent>
 */
class MailboxesSynchronizedSpecialMailboxesUpdater implements IEventListener {
	/** @var MailAccountMapper */
	private $mailAccountMapper;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(MailAccountMapper $mailAccountMapper,
		MailboxMapper $mailboxMapper,
		LoggerInterface $logger) {
		$this->mailAccountMapper = $mailAccountMapper;
		$this->mailboxMapper = $mailboxMapper;
		$this->logger = $logger;
	}

	/**
	 * @param Event $event
	 */
	#[\Override]
	public function handle(Event $event): void {
		/** @var MailboxesSynchronizedEvent $event */
		$account = $event->getAccount();
		$mailAccount = $account->getMailAccount();
		$mailboxes = $this->indexMailboxes(
			$this->mailboxMapper->findAll($account)
		);

		if ($mailAccount->getDraftsMailboxId() === null || !array_key_exists($mailAccount->getDraftsMailboxId(), $mailboxes)) {
			try {
				$draftsMailbox = $this->findSpecial($mailboxes, 'drafts');
				$mailAccount->setDraftsMailboxId($draftsMailbox->getId());
			} catch (DoesNotExistException $e) {
				$this->logger->info('Account ' . $account->getId() . ' does not have a drafts mailbox');

				$mailAccount->setDraftsMailboxId(null);
			}
		}
		if ($mailAccount->getSentMailboxId() === null || !array_key_exists($mailAccount->getSentMailboxId(), $mailboxes)) {
			try {
				$sentMailbox = $this->findSpecial($mailboxes, 'sent');
				$mailAccount->setSentMailboxId($sentMailbox->getId());
			} catch (DoesNotExistException $e) {
				$this->logger->info('Account ' . $account->getId() . ' does not have a sent mailbox');

				$mailAccount->setSentMailboxId(null);
			}
		}
		if ($mailAccount->getTrashMailboxId() === null || !array_key_exists($mailAccount->getTrashMailboxId(), $mailboxes)) {
			try {
				$trashMailbox = $this->findSpecial($mailboxes, 'trash');
				$mailAccount->setTrashMailboxId($trashMailbox->getId());
			} catch (DoesNotExistException $e) {
				$this->logger->info('Account ' . $account->getId() . ' does not have a trash mailbox');

				$mailAccount->setTrashMailboxId(null);
			}
		}
		if ($mailAccount->getArchiveMailboxId() === null || !array_key_exists($mailAccount->getArchiveMailboxId(), $mailboxes)) {
			try {
				$archiveMailbox = $this->findSpecial($mailboxes, 'archive');
				$mailAccount->setArchiveMailboxId($archiveMailbox->getId());
			} catch (DoesNotExistException $e) {
				$this->logger->info('Account ' . $account->getId() . ' does not have an archive mailbox');

				$mailAccount->setArchiveMailboxId(null);
			}
		}
		if ($mailAccount->getJunkMailboxId() === null || !array_key_exists($mailAccount->getJunkMailboxId(), $mailboxes)) {
			try {
				$junkMailbox = $this->findSpecial($mailboxes, 'junk');
				$mailAccount->setJunkMailboxId($junkMailbox->getId());
			} catch (DoesNotExistException) {
				$this->logger->info('Account ' . $account->getId() . ' does not have an junk mailbox');
				$mailAccount->setJunkMailboxId(null);
			}
		}
		if ($mailAccount->getSnoozeMailboxId() === null || !array_key_exists($mailAccount->getSnoozeMailboxId(), $mailboxes)) {
			try {
				$snoozeMailbox = $this->findSpecial($mailboxes, 'snooze');
				$mailAccount->setSnoozeMailboxId($snoozeMailbox->getId());
			} catch (DoesNotExistException $e) {
				$this->logger->info('Account ' . $account->getId() . ' does not have an snooze mailbox');

				$mailAccount->setSnoozeMailboxId(null);
			}
		}

		$this->mailAccountMapper->update($mailAccount);
	}

	private function indexMailboxes(array $mailboxes): array {
		return array_combine(
			array_map(static function (Mailbox $mb) : int {
				return $mb->getId();
			}, $mailboxes),
			$mailboxes
		);
	}

	/**
	 * @param Mailbox[] $mailboxes
	 * @throws DoesNotExistException
	 */
	private function findSpecial(array $mailboxes, string $specialUse): Mailbox {
		// First, let's try to detect by special use attribute
		foreach ($mailboxes as $mailbox) {
			$specialUses = json_decode($mailbox->getSpecialUse(), true) ?? [];
			if (in_array($specialUse, $specialUses, true)) {
				return $mailbox;
			}
		}

		// No luck so far, let's do another round and just guess
		foreach ($mailboxes as $mailbox) {
			// TODO: also check localized name
			if (strtolower($mailbox->getName()) === strtolower($specialUse)) {
				return $mailbox;
			}
		}

		// Give up
		throw new DoesNotExistException("Special mailbox $specialUse does not exist");
	}
}
