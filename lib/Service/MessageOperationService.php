<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use Throwable;

class MessageOperationService {

	public function __construct(
		protected IMAPClientFactory $clientFactory,
		protected MailAccountMapper $accountMapper,
		protected MailboxMapper $mailboxMapper,
		protected MessageMapper $messageMapper,
		protected MailManager $mailManager,
		protected ImapMessageMapper $imapMessageMapper,
	) {
	}

	/**
	 * convert message collection to grouped collections by mailbox id
	 *
	 * [[mailbox_id, uid, id]] to [mailbox_id => [[id, uid]]]
	 *
	 * @param array<array{0:int,1:int,2:int}> $collection
	 *
	 * @return array<array-key, mixed|non-empty-list<array{id: int, uid: int}>>|mixed
	 */
	protected function groupByMailbox(array $collection): array {
		return array_reduce($collection, function ($carry, $pair) {
			if (!isset($carry[$pair['mailbox_id']])) {
				$carry[$pair['mailbox_id']] = [];
			}
			$carry[(int)$pair['mailbox_id']][] = ['id' => (int)$pair['id'], 'uid' => (int)$pair['uid']];
			return $carry;
		}, []);
	}

	/**
	 * convert mailbox collection to grouped collections by account id
	 *
	 * [mailbox] to [account_id => [mailbox]]
	 *
	 * @param array<\OCA\Mail\Db\Mailbox> $collection
	 *
	 * @return array<int,array<\OCA\Mail\Db\Mailbox>>
	 */
	protected function groupByAccount(array $collection) {
		return array_reduce($collection, function ($carry, $entry) {
			if (!isset($carry[$entry->getAccountId()])) {
				$carry[$entry->getAccountId()] = [];
			}
			$carry[$entry->getAccountId()][] = $entry;
			return $carry;
		}, []);
	}

	/**
	 * generates operation status responses for each message
	 *
	 * @param array<int,bool> &$results
	 * @param bool $value
	 * @param array<\OCA\Mail\Db\Mailbox> $mailboxes
	 * @param array<int, array<int, array{id: int, uid: int}>> $messages
	 */
	protected function generateResult(array &$results, bool $value, array $mailboxes, array $messages) {
		foreach ($mailboxes as $mailbox) {
			foreach ($messages[$mailbox->getId()] as $message) {
				$results[$message['id']] = $value;
			}
		}
	}

	/**
	 * Set/Unset system flags or keywords
	 *
	 * @param string $userId system user id
	 * @param array<int> $identifiers message ids
	 * @param array<string,bool> $flags message flags
	 *
	 * @return array<int,bool> operation results
	 */
	public function changeFlags(string $userId, array $identifiers, array $flags): array {

		// retrieve message meta data [uid, mailbox_id] for all messages and group by mailbox id
		$messages = $this->groupByMailbox($this->messageMapper->findMailboxAndUid($identifiers));
		// retrieve all mailboxes and group by account
		$mailboxes = $this->groupByAccount($this->mailboxMapper->findByIds(array_keys($messages)));
		// retrieve all accounts
		$accounts = $this->accountMapper->findByIds($userId, array_keys($mailboxes));
		// process every account
		$results = [];
		foreach ($accounts as $account) {
			$account = new Account($account);
			$client = $this->clientFactory->getClient($account);
			// process every mailbox
			foreach ($mailboxes[$account->getId()] as $mailbox) {
				try {
					// check if specific flags are supported and group them by action
					$addFlags = [];
					$removeFlags = [];
					foreach ($flags as $flag => $value) {
						$value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
						$imapFlags = $this->mailManager->filterFlags($client, $account, $flag, $mailbox->getName());
						if (empty($imapFlags)) {
							continue;
						}
						if ($value) {
							$addFlags = array_merge($addFlags, $imapFlags);
						} else {
							$removeFlags = array_merge($removeFlags, $imapFlags);
						}
					}
					// apply flags to messages on server
					$this->imapMessageMapper->setFlags(
						$client,
						$mailbox,
						array_column($messages[$mailbox->getId()], 'uid'),
						$addFlags,
						$removeFlags
					);
					// add messages to results as successful
					$this->generateResult($results, true, [$mailbox], $messages);
				} catch (Throwable $e) {
					// add messages to results as failed
					$this->generateResult($results, false, [$mailbox], $messages);
				}
			}
			$client->logout();
		}
		
		return $results;
	}

}
