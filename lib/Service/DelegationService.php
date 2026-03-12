<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Db\AliasMapper;
use OCA\Mail\Db\Delegation;
use OCA\Mail\Db\DelegationMapper;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\DelegationExistsException;
use OCP\AppFramework\Db\DoesNotExistException;

class DelegationService {

	public function __construct(
		private DelegationMapper $delegationMapper,
		private AccountService $accountService,
		private MailboxMapper $mailboxMapper,
		private MessageMapper $messageMapper,
		private AliasMapper $aliasMapper,
		private LocalMessageMapper $localMessageMapper,
	) {
	}

	public function delegate(int $accountId, string $userId): Delegation {
		try {
			$this->delegationMapper->find($accountId, $userId);
			throw new DelegationExistsException("Delegation already exists for account $accountId and user $userId");
		} catch (DoesNotExistException) {
			// delegation doesn't exist, continue
		}

		$delegation = new Delegation();
		$delegation->setAccountId($accountId);
		$delegation->setUserId($userId);
		return $this->delegationMapper->insert($delegation);
	}

	public function findDelegatedToUsersForAccount(int $accountId): array {
		return $this->delegationMapper->findDelegatedToUsers($accountId);
	}

	public function unDelegate(int $accountId, string $userId): void {
		try {
			$delegation = $this->delegationMapper->find($accountId, $userId);
			$this->delegationMapper->delete($delegation);
		} catch (DoesNotExistException $e) {
			// shouldn't end up here
			// delegation not found nothing to undelegate
		}
	}

	/**
	 * @throws ClientException
	 */
	public function resolveAccountUserId(int $accountId, string $currentUserId): string {
		// Check if the current user owns the account
		try {
			$this->accountService->find($currentUserId, $accountId);
			return $currentUserId;
		} catch (ClientException) {
			// Not the owner — check delegation
		}
		try {
			return $this->delegationMapper->findAccountOwnerForDelegatedUser($accountId, $currentUserId);
		} catch (DoesNotExistException) {
			throw new ClientException("no Delegated account with id: <{$accountId}> exists for user <{$currentUserId}>");
		}
	}

	/**
	 * @throws DoesNotExistException
	 * @throws ClientException
	 */
	public function resolveMailboxUserId(int $mailboxId, string $currentUserId): string {
		$accountId = $this->mailboxMapper->findAccountIdForMailbox($mailboxId);
		return $this->resolveAccountUserId($accountId, $currentUserId);
	}

	/**
	 * @throws DoesNotExistException
	 * @throws ClientException
	 */
	public function resolveMessageUserId(int $messageId, string $currentUserId): string {
		$accountId = $this->messageMapper->findAccountIdForMessage($messageId);
		return $this->resolveAccountUserId($accountId, $currentUserId);
	}

	/**
	 * @throws DoesNotExistException
	 * @throws ClientException
	 */
	public function resolveAliasUserId(int $aliasId, string $currentUserId): string {
		$accountId = $this->aliasMapper->findAccountIdForAlias($aliasId);
		return $this->resolveAccountUserId($accountId, $currentUserId);
	}

	/**
	 * @throws DoesNotExistException
	 * @throws ClientException
	 */
	public function resolveLocalMessageUserId(int $localMessageId, string $currentUserId): string {
		$accountId = $this->localMessageMapper->findAccountIdForLocalMessage($localMessageId);
		return $this->resolveAccountUserId($accountId, $currentUserId);
	}
}
