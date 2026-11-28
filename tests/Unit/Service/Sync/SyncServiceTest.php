<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Sync;

use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\MailboxLockedException;
use OCA\Mail\Exception\MailboxNotCachedException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MailboxStats;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\IMAP\PreviewEnhancer;
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\Service\Search\FilterStringParser;
use OCA\Mail\Service\Sync\ImapToDbSynchronizer;
use OCA\Mail\Service\Sync\SyncService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SyncServiceTest extends TestCase {
	private const NOW = 1_000_000;

	private IMAPClientFactory&MockObject $clientFactory;

	private ITimeFactory&MockObject $timeFactory;

	/** @var ImapToDbSynchronizer */
	private $synchronizer;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var MailboxSync */
	private $mailboxSync;

	/** @var SyncService */
	private $syncService;

	protected function setUp(): void {
		parent::setUp();

		$this->clientFactory = $this->createMock(IMAPClientFactory::class);
		$this->synchronizer = $this->createMock(ImapToDbSynchronizer::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->mailboxSync = $this->createMock(MailboxSync::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->syncService = new SyncService(
			$this->clientFactory,
			$this->synchronizer,
			$this->createStub(FilterStringParser::class),
			$this->messageMapper,
			$this->createStub(PreviewEnhancer::class),
			$this->createStub(\Psr\Log\LoggerInterface::class),
			$this->mailboxSync,
			$this->timeFactory,
		);
	}

	public function testPartialSyncOnUncachedMailbox(): void {
		$account = $this->createStub(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$mailbox->expects($this->once())
			->method('isCached')
			->willReturn(false);

		$this->expectException(MailboxNotCachedException::class);
		$this->syncService->syncMailbox(
			$account,
			$mailbox,
			42,
			true,
			null,
			[],
			'DESC'
		);
	}

	public static function lockProvider(): \Iterator {
		yield 'new messages, initial sync' => ['setSyncNewLock', false];
		yield 'changed messages, partial sync' => ['setSyncChangedLock', true];
		yield 'vanished messages, partial sync' => ['setSyncVanishedLock', true];
	}

	/**
	 * @dataProvider lockProvider
	 */
	public function testSyncLockedMailbox(string $setLock, bool $partialOnly): void {
		$account = $this->createStub(Account::class);
		$mailbox = $this->createCachedMailbox();
		$mailbox->$setLock(self::NOW);
		$this->timeFactory->method('getTime')->willReturn(self::NOW);
		$this->mailboxConnector->expects($this->never())->method('syncOne');
		$this->messageConnector->expects($this->never())->method('syncMailbox');
		$this->expectException(MailboxLockedException::class);

		$this->syncService->syncMailbox($account, $mailbox, 0, $partialOnly, null);
	}

	public function testPartialSyncOfALockedUncachedMailbox(): void {
		$account = $this->createStub(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setSyncNewLock(self::NOW);
		$this->timeFactory->method('getTime')->willReturn(self::NOW);
		$this->mailboxConnector->expects($this->never())->method('syncOne');
		$this->expectException(MailboxNotCachedException::class);

		$this->syncService->syncMailbox($account, $mailbox, 0, true, null);
	}

	public function testSyncMailboxWithAnExpiredLock(): void {
		$account = $this->createStub(Account::class);
		$mailbox = $this->createCachedMailbox();
		$mailbox->setSyncNewLock(self::NOW - Mailbox::LOCK_TIMEOUT);
		$this->timeFactory->method('getTime')->willReturn(self::NOW);
		$this->mailboxConnector->expects($this->once())
			->method('syncOne')
			->with($account, $mailbox);
		$this->messageConnector->expects($this->once())
			->method('syncMailbox')
			->with($account, $mailbox, $this->isInstanceOf(\Psr\Log\LoggerInterface::class), 0, null, true)
			->willReturn(new \OCA\Mail\Protocol\SyncResult());

		$this->syncService->syncMailbox($account, $mailbox, 0, false, null);
	}

	public function testSyncMailboxReturnsFolderStats(): void {
		$account = $this->createMock(Account::class);
		$account->method('getUserId')->willReturn('user');
		$mailbox = new Mailbox();
		$mailbox->setMessages(42);
		$mailbox->setUnseen(10);
		$expectedResponse = new Response(
			[],
			[],
			[],
			new MailboxStats(42, 10, null)
		);
		$this->clientFactory
			->method('getClient')
			->with($account)
			->willReturn($this->createStub(\Horde_Imap_Client_Socket::class));
		$this->messageMapper
			->method('findUidsForIds')
			->with($mailbox, [])
			->willReturn([]);
		$this->synchronizer->expects($this->once())
			->method('sync')
			->with(
				$account,
				$this->createStub(\Horde_Imap_Client_Socket::class),
				$mailbox,
				$this->createStub(\Psr\Log\LoggerInterface::class),
				0,
				[],
				true
			);
		$this->mailboxSync->expects($this->once())
			->method('syncStats')
			->with($this->createStub(\Horde_Imap_Client_Socket::class), $mailbox);

		$response = $this->syncService->syncMailbox(
			$account,
			$mailbox,
			0,
			false,
			null,
			[]
		);

		$this->assertEquals($expectedResponse, $response);
	}

	public function testInitialSyncReturnsNoMessages(): void {
		$account = $this->createMock(Account::class);
		$account->method('getUserId')->willReturn('user');
		$mailbox = new Mailbox();
		$mailbox->setMessages(33000);
		$mailbox->setUnseen(24000);
		$this->clientFactory
			->method('getClient')
			->willReturn($this->createStub(\Horde_Imap_Client_Socket::class));
		$this->synchronizer->expects($this->once())
			->method('sync');
		// The client loads the first page itself, so the cache is not read
		$this->messageMapper->expects($this->never())
			->method('findAllIds');
		$this->messageMapper->expects($this->never())
			->method('findNewIds');
		$this->messageMapper->expects($this->never())
			->method('findByMailboxAndIds');

		$response = $this->syncService->syncMailbox(
			$account,
			$mailbox,
			0,
			false,
			null,
			[]
		);

		$this->assertEquals(new Response([], [], [], new MailboxStats(33000, 24000, null)), $response);
	}

	public function testPartialSyncReadsTheCache(): void {
		$account = $this->createMock(Account::class);
		$account->method('getUserId')->willReturn('user');
		$mailbox = new Mailbox();
		$mailbox->setMessages(3);
		$mailbox->setUnseen(1);
		$mailbox->setSyncNewToken('token');
		$mailbox->setSyncChangedToken('token');
		$mailbox->setSyncVanishedToken('token');
		$this->clientFactory
			->method('getClient')
			->willReturn($this->createStub(\Horde_Imap_Client_Socket::class));
		$this->synchronizer->expects($this->once())
			->method('sync');
		$this->messageMapper->expects($this->once())
			->method('findNewIds')
			->with($mailbox, [1, 2], null, 'DESC')
			->willReturn([3]);
		$this->messageMapper->method('findByMailboxAndIds')
			->willReturn([]);

		$this->syncService->syncMailbox(
			$account,
			$mailbox,
			0,
			true,
			null,
			[1, 2]
		);
	}

	private function createCachedMailbox(): Mailbox {
		$mailbox = new Mailbox();
		$mailbox->setMessages(0);
		$mailbox->setUnseen(0);
		$mailbox->setSyncNewToken('abc');
		$mailbox->setSyncChangedToken('def');
		$mailbox->setSyncVanishedToken('ghi');
		return $mailbox;
	}
}
