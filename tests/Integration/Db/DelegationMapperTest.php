<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Delegation;
use OCA\Mail\Db\DelegationMapper;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IDBConnection;
use OCP\Server;

/**
 * @group DB
 * @covers \OCA\Mail\Db\DelegationMapper
 */
class DelegationMapperTest extends TestCase {
	use DatabaseTransaction;

	private IDBConnection $db;
	private DelegationMapper $mapper;
	private MailAccountMapper $accountMapper;

	protected function setUp(): void {
		parent::setUp();

		$this->db = Server::get(IDBConnection::class);
		$this->mapper = new DelegationMapper($this->db);
		$this->accountMapper = new MailAccountMapper($this->db);
	}

	private function createDelegation(int $accountId, string $userId): Delegation {
		$delegation = new Delegation();
		$delegation->setAccountId($accountId);
		$delegation->setUserId($userId);
		return $this->mapper->insert($delegation);
	}

	private function createAccount(string $ownerUid): MailAccount {
		$account = new MailAccount();
		$account->setName('Owner');
		$account->setEmail('owner@example.com');
		$account->setUserId($ownerUid);
		return $this->accountMapper->insert($account);
	}

	public function testFindDelegatedToUsers(): void {
		$accountId = $this->createAccount('owner-a')->getId();
		$otherAccountId = $this->createAccount('owner-b')->getId();
		$this->createDelegation($accountId, 'alice');
		$this->createDelegation($accountId, 'bob');
		$this->createDelegation($otherAccountId, 'carol');

		$result = $this->mapper->findDelegatedToUsers($accountId);

		$this->assertCount(2, $result);
		$uids = array_map(static fn (Delegation $d) => $d->getUserId(), $result);
		$this->assertEqualsCanonicalizing(['alice', 'bob'], $uids);
	}

	public function testFindDelegatedToUsersReturnsEmpty(): void {
		$accountId = $this->createAccount('owner-empty')->getId();

		$this->assertSame([], $this->mapper->findDelegatedToUsers($accountId));
	}

	public function testFind(): void {
		$accountId = $this->createAccount('owner-c')->getId();
		$inserted = $this->createDelegation($accountId, 'dave');

		$result = $this->mapper->find($accountId, 'dave');

		$this->assertSame($inserted->getId(), $result->getId());
		$this->assertSame('dave', $result->getUserId());
		$this->assertSame($accountId, $result->getAccountId());
	}

	public function testFindThrowsWhenMissing(): void {
		$accountId = $this->createAccount('owner-d')->getId();

		$this->expectException(DoesNotExistException::class);

		$this->mapper->find($accountId, 'nobody');
	}

	public function testFindAccountOwnerForDelegatedUser(): void {
		$account = $this->createAccount('owner-uid');
		$this->createDelegation($account->getId(), 'delegate');

		$owner = $this->mapper->findAccountOwnerForDelegatedUser($account->getId(), 'delegate');

		$this->assertSame('owner-uid', $owner);
	}

	public function testFindAccountOwnerThrowsWhenNoDelegation(): void {
		$account = $this->createAccount('owner-uid');

		$this->expectException(DoesNotExistException::class);

		$this->mapper->findAccountOwnerForDelegatedUser($account->getId(), 'delegate');
	}

	public function testDeleteByUserId(): void {
		$accountA = $this->createAccount('owner-a')->getId();
		$accountB = $this->createAccount('owner-b')->getId();
		$this->createDelegation($accountA, 'delegate');
		$this->createDelegation($accountB, 'delegate');
		$this->createDelegation($accountA, 'other');

		$this->mapper->deleteByUserId('delegate');

		$remaining = array_map(
			static fn (Delegation $d) => $d->getUserId(),
			$this->mapper->findDelegatedToUsers($accountA),
		);
		$this->assertSame(['other'], $remaining);
		$this->assertSame([], $this->mapper->findDelegatedToUsers($accountB));
	}

	public function testDeleteByUserIdNoMatch(): void {
		$accountId = $this->createAccount('owner-uid')->getId();
		$this->createDelegation($accountId, 'delegate');

		$this->mapper->deleteByUserId('nobody');

		$this->assertCount(1, $this->mapper->findDelegatedToUsers($accountId));
	}
}
