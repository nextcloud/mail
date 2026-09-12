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
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\DelegationExistsException;
use OCA\Mail\Exception\DelegationForbiddenException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUserManager;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Notification\IManager;
use OCP\Notification\IncompleteNotificationException;
use Psr\Log\LoggerInterface;

class DelegationService {

	public function __construct(
		private DelegationMapper $delegationMapper,
		private AccountService $accountService,
		private MailboxMapper $mailboxMapper,
		private MessageMapper $messageMapper,
		private AliasMapper $aliasMapper,
		private LocalMessageMapper $localMessageMapper,
		private IUserManager $userManager,
		private IManager $notificationManager,
		private ITimeFactory $time,
		private IEventDispatcher $eventDispatcher,
		private LoggerInterface $logger,
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

	public function deleteByUserId(string $userId): void {
		$this->delegationMapper->deleteByUserId($userId);
	}

	public function findDelegatedToUsersForAccount(int $accountId): array {
		return array_map(function (Delegation $delegation) {
			$displayName = $this->userManager->get($delegation->getUserId())?->getDisplayName();
			$delegation->setDisplayName($displayName);
			return $delegation;
		}, $this->delegationMapper->findDelegatedToUsers($accountId));
	}

	public function unDelegate(Account $account, string $userId, string $currentUserId): void {
		try {
			$accountId = $account->getId();
			$delegation = $this->delegationMapper->find($accountId, $userId);
			$this->delegationMapper->delete($delegation);
			$this->notify($userId, $currentUserId, $account, false);
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
	 * Assert that the current user may act on the given account.
	 *
	 * Use this for account ids that are supplied by the client on top of the id the
	 * effective user was resolved from. Resolving those with the effective user id
	 * would accept any account of the account owner, not just the delegated ones.
	 *
	 * @throws DelegationForbiddenException
	 */
	public function assertAccountAccess(int $accountId, string $currentUserId): void {
		try {
			$this->resolveAccountUserId($accountId, $currentUserId);
		} catch (ClientException $e) {
			throw new DelegationForbiddenException($e->getMessage(), 0, $e);
		}
	}

	/**
	 * Assert that the current user may act on the account the given mailbox belongs to.
	 *
	 * @throws DoesNotExistException
	 * @throws DelegationForbiddenException
	 */
	public function assertMailboxAccess(int $mailboxId, string $currentUserId): void {
		$this->assertAccountAccess(
			$this->mailboxMapper->findAccountIdForMailbox($mailboxId),
			$currentUserId,
		);
	}

	/**
	 * Assert that the current user may act on the account the given message belongs to.
	 *
	 * @throws DoesNotExistException
	 * @throws DelegationForbiddenException
	 */
	public function assertMessageAccess(int $messageId, string $currentUserId): void {
		$this->assertAccountAccess(
			$this->messageMapper->findAccountIdForMessage($messageId),
			$currentUserId,
		);
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

	public function logDelegatedAction(string $currentUserId, string $effectiveUserId, string $logMessage): void {
		if ($currentUserId === $effectiveUserId) {
			return;
		}
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

		try {
			$this->notificationManager->notify($notification);
		} catch (IncompleteNotificationException $e) {
			$this->logger->warning('Failed to send delegation notification to {userId}', ['userId' => $userId,'exception' => $e]);
		}
	}
}
