<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\AliasMapper;
use OCA\Mail\Db\Delegation;
use OCA\Mail\Db\DelegationMapper;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\DelegationExistsException;
use OCA\Mail\Service\DelegationService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use PHPUnit\Framework\MockObject\MockObject;

class DelegationServiceTest extends TestCase {
	private DelegationMapper&MockObject $delegationMapper;
	private MailAccountMapper&MockObject $mailAccountMapper;
	private MailboxMapper&MockObject $mailboxMapper;
	private MessageMapper&MockObject $messageMapper;
	private AliasMapper&MockObject $aliasMapper;
	private LocalMessageMapper&MockObject $localMessageMapper;
	private IUserManager&MockObject $userManager;
	private IManager&MockObject $notificationManager;
	private ITimeFactory&MockObject $timeFactory;
	private IEventDispatcher&MockObject $eventDispatcher;
	private DelegationService $service;

	private Account $account;

	protected function setUp(): void {
		parent::setUp();

		$this->delegationMapper = $this->createMock(DelegationMapper::class);
		$this->mailAccountMapper = $this->createMock(MailAccountMapper::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->aliasMapper = $this->createMock(AliasMapper::class);
		$this->localMessageMapper = $this->createMock(LocalMessageMapper::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->notificationManager = $this->createMock(IManager::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->service = new DelegationService(
			$this->delegationMapper,
			$this->mailAccountMapper,
			$this->mailboxMapper,
			$this->messageMapper,
			$this->aliasMapper,
			$this->localMessageMapper,
		);

		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('owner');
		$mailAccount->setEmail('owner@example.com');
		$this->account = new Account($mailAccount);
	}

	private function mockNotification(): void {
		$notification = $this->createMock(INotification::class);
		$notification->method('setApp')->willReturnSelf();
		$notification->method('setUser')->willReturnSelf();
		$notification->method('setObject')->willReturnSelf();
		$notification->method('setSubject')->willReturnSelf();
		$notification->method('setDateTime')->willReturnSelf();
		$notification->method('setMessage')->willReturnSelf();

		$this->notificationManager->method('createNotification')->willReturn($notification);

		$user = $this->createMock(IUser::class);
		$user->method('getDisplayName')->willReturn('Owner User');
		$this->userManager->method('get')->with('owner')->willReturn($user);
		$this->timeFactory->method('getDateTime')->willReturn(new \DateTime());
	}

	public function testDelegateSuccess(): void {
		$this->mockNotification();

		$this->delegationMapper->expects($this->once())
			->method('find')
			->with(1, 'delegatee')
			->willThrowException(new DoesNotExistException('Not found'));

		$expected = new Delegation();
		$expected->setAccountId(1);
		$expected->setUserId('delegatee');

		$this->delegationMapper->expects($this->once())
			->method('insert')
			->willReturnCallback(function (Delegation $d) {
				$d->setId(10);
				return $d;
			});

		$result = $this->service->delegate($this->account->getId(), 'delegatee');

		$this->assertEquals(1, $result->getAccountId());
		$this->assertEquals('delegatee', $result->getUserId());
	}

	public function testDelegateThrowsWhenAlreadyExists(): void {
		$existing = new Delegation();
		$existing->setAccountId(1);
		$existing->setUserId('delegatee');

		$this->delegationMapper->expects($this->once())
			->method('find')
			->with(1, 'delegatee')
			->willReturn($existing);

		$this->delegationMapper->expects($this->never())
			->method('insert');

		$this->expectException(DelegationExistsException::class);

		$this->service->delegate($this->account->getId(), 'delegatee');
	}

	public function testFindDelegatedToUsersForAccount(): void {
		$delegation = new Delegation();
		$delegation->setAccountId(1);
		$delegation->setUserId('delegatee');

		$this->delegationMapper->expects($this->once())
			->method('findDelegatedToUsers')
			->with(1)
			->willReturn([$delegation]);

		$result = $this->service->findDelegatedToUsersForAccount(1);

		$this->assertCount(1, $result);
		$this->assertEquals('delegatee', $result[0]->getUserId());
	}

	public function testUnDelegateSuccess(): void {
		$this->mockNotification();

		$delegation = new Delegation();
		$delegation->setId(10);
		$delegation->setAccountId(1);
		$delegation->setUserId('delegatee');

		$this->delegationMapper->expects($this->once())
			->method('find')
			->with(1, 'delegatee')
			->willReturn($delegation);

		$this->delegationMapper->expects($this->once())
			->method('delete')
			->with($delegation);

		$this->service->unDelegate($this->account->getId(), 'delegatee');
	}

	public function testUnDelegateThrowsWhenNotFound(): void {
		$this->delegationMapper->expects($this->once())
			->method('find')
			->with(1, 'delegatee')
			->willThrowException(new DoesNotExistException('Not found'));

		$this->delegationMapper->expects($this->never())
			->method('delete');

		$this->expectException(DoesNotExistException::class);

		$this->service->unDelegate($this->account->getId(), 'delegatee');
	}

	public function testResolveAccountUserIdOwner(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('owner');

		$this->mailAccountMapper->expects($this->once())
			->method('find')
			->with('owner', 1)
			->willReturn($mailAccount);

		$result = $this->service->resolveAccountUserId(1, 'owner');

		$this->assertEquals('owner', $result);
	}

	public function testResolveAccountUserIdDelegated(): void {
		$this->mailAccountMapper->expects($this->once())
			->method('find')
			->with('delegatee', 1)
			->willThrowException(new DoesNotExistException('Not found'));

		$this->delegationMapper->expects($this->once())
			->method('findAccountOwnerForDelegatedUser')
			->with(1, 'delegatee')
			->willReturn('owner');

		$result = $this->service->resolveAccountUserId(1, 'delegatee');

		$this->assertEquals('owner', $result);
	}

	public function testResolveAccountUserIdNotFound(): void {
		$this->mailAccountMapper->expects($this->once())
			->method('find')
			->with('stranger', 1)
			->willThrowException(new DoesNotExistException('Not found'));

		$this->delegationMapper->expects($this->once())
			->method('findAccountOwnerForDelegatedUser')
			->with(1, 'stranger')
			->willThrowException(new DoesNotExistException('No delegation found'));

		$this->expectException(DoesNotExistException::class);

		$this->service->resolveAccountUserId(1, 'stranger');
	}

	public function testResolveMailboxUserId(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('owner');

		$this->mailboxMapper->expects($this->once())
			->method('findAccountIdForMailbox')
			->with(42)
			->willReturn(1);

		$this->mailAccountMapper->expects($this->once())
			->method('find')
			->with('owner', 1)
			->willReturn($mailAccount);

		$result = $this->service->resolveMailboxUserId(42, 'owner');

		$this->assertEquals('owner', $result);
	}

	public function testResolveMessageUserId(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('owner');

		$this->messageMapper->expects($this->once())
			->method('findAccountIdForMessage')
			->with(99)
			->willReturn(1);

		$this->mailAccountMapper->expects($this->once())
			->method('find')
			->with('owner', 1)
			->willReturn($mailAccount);

		$result = $this->service->resolveMessageUserId(99, 'owner');

		$this->assertEquals('owner', $result);
	}

	public function testResolveAliasUserId(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('owner');

		$this->aliasMapper->expects($this->once())
			->method('findAccountIdForAlias')
			->with(7)
			->willReturn(1);

		$this->mailAccountMapper->expects($this->once())
			->method('find')
			->with('owner', 1)
			->willReturn($mailAccount);

		$result = $this->service->resolveAliasUserId(7, 'owner');

		$this->assertEquals('owner', $result);
	}

	public function testResolveLocalMessageUserId(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('owner');

		$this->localMessageMapper->expects($this->once())
			->method('findAccountIdForLocalMessage')
			->with(55)
			->willReturn(1);

		$this->mailAccountMapper->expects($this->once())
			->method('find')
			->with('owner', 1)
			->willReturn($mailAccount);

		$result = $this->service->resolveLocalMessageUserId(55, 'owner');

		$this->assertEquals('owner', $result);
	}
}
