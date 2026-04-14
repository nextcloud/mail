<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\Db\AliasMapper;
use OCA\Mail\Db\Delegation;
use OCA\Mail\Db\DelegationMapper;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\DelegationExistsException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUserManager;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Notification\IManager;

class DelegationService {

	public function __construct(
		private DelegationMapper $delegationMapper,
		private MailAccountMapper $mailAccountMapper,
		private MailboxMapper $mailboxMapper,
		private MessageMapper $messageMapper,
		private AliasMapper $aliasMapper,
		private LocalMessageMapper $localMessageMapper,
		private IUserManager $userManager,
		private IManager $notificationManager,
		private ITimeFactory $time,
		private IEventDispatcher $eventDispatcher,
	) {
	}

	public function delegate(Account $account, string $userId, string $currentUserId): Delegation {
		$accountId = $account->getId();
		try {
			$this->delegationMapper->find($accountId, $userId);
			throw new DelegationExistsException("Delegation already exists for account $accountId and user $userId");
		} catch (DoesNotExistException) {
			// delegation doesn't exist, continue
		}

		$delegation = new Delegation();
		$delegation->setAccountId($accountId);
		$delegation->setUserId($userId);
		$result = $this->delegationMapper->insert($delegation);
		$this->notify($userId, $currentUserId, $account, true);
		return $result;
	}

	public function findDelegatedToUsersForAccount(int $accountId): array {
		return $this->delegationMapper->findDelegatedToUsers($accountId);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function unDelegate(Account $account, string $userId, string $currentUserId): void {
		$accountId = $account->getId();
		$delegation = $this->delegationMapper->find($accountId, $userId);
		$this->delegationMapper->delete($delegation);
		$this->notify($userId, $currentUserId, $account, false);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function resolveAccountUserId(int $accountId, string $currentUserId): string {
		// Check if the current user owns the account
		try {
			$account = $this->mailAccountMapper->find($currentUserId, $accountId);
			return $account->getUserId();
		} catch (DoesNotExistException) {
			// Not the owner — check delegation
		}

		return $this->delegationMapper->findAccountOwnerForDelegatedUser($accountId, $currentUserId);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function resolveMailboxUserId(int $mailboxId, string $currentUserId): string {
		$accountId = $this->mailboxMapper->findAccountIdForMailbox($mailboxId);
		return $this->resolveAccountUserId($accountId, $currentUserId);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function resolveMessageUserId(int $messageId, string $currentUserId): string {
		$accountId = $this->messageMapper->findAccountIdForMessage($messageId);
		return $this->resolveAccountUserId($accountId, $currentUserId);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function resolveAliasUserId(int $aliasId, string $currentUserId): string {
		$accountId = $this->aliasMapper->findAccountIdForAlias($aliasId);
		return $this->resolveAccountUserId($accountId, $currentUserId);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function resolveLocalMessageUserId(int $localMessageId, string $currentUserId): string {
		$accountId = $this->localMessageMapper->findAccountIdForLocalMessage($localMessageId);
		return $this->resolveAccountUserId($accountId, $currentUserId);
	}


	public function logDelegatedAction(string $logMessage) {
		$this->eventDispatcher->dispatchTyped(new CriticalActionPerformedEvent($logMessage));
	}

	/**
	 * Send a notification on delegation
	 * @param string $userId The user the account is being delegated to
	 * @param string $currentUserId Current user
	 * @param Account $account The delegated account
	 * @param bool $delegated true for delegate|false for undelegate
	 * @return void
	 */
	private function notify(string $userId, string $currentUserId, Account $account, bool $delegated) {
		$notification = $this->notificationManager->createNotification();
		$displayName = $this->userManager->get($currentUserId)?->getDisplayName() ?? $currentUserId;
		$time = $this->time->getDateTime('now');
		$notification
			->setApp('mail')
			->setUser($userId)
			->setObject('delegation', (string)$account->getId())
			->setSubject('account_delegation', [
				'id' => $account->getId(),
				'account_email' => $account->getEmail(),

			])
			->setDateTime($time)
			->setMessage('account_delegation_changed', [
				'id' => $account->getId(),
				'delegated' => $delegated,
				'current_user_id' => $currentUserId,
				'current_user_display_name' => $displayName,
				'account_email' => $account->getEmail(),
			]
			);
		$this->notificationManager->notify($notification);
	}
}
