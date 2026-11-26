<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Sync;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Base;
use Horde_Imap_Client_Data_Capability_Imap;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper as DatabaseMessageMapper;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Events\SynchronizationEvent;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\IMAP\Sync\Synchronizer;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\Classification\NewMessagesClassifier;
use OCA\Mail\Service\Sync\ImapToDbSynchronizer;
use OCA\Mail\Support\PerformanceLogger;
use OCA\Mail\Support\PerformanceLoggerTask;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use function array_fill;
use function array_reverse;
use function count;
use function range;

class ImapToDbSynchronizerTest extends TestCase {
	private DatabaseMessageMapper&MockObject $dbMapper;
	private IMAPClientFactory&MockObject $clientFactory;
	private ImapMessageMapper&MockObject $imapMapper;
	private MailboxMapper&MockObject $mailboxMapper;
	private Synchronizer&MockObject $imapSynchronizer;
	private IEventDispatcher&MockObject $dispatcher;
	private PerformanceLogger&MockObject $performanceLogger;
	private ImapToDbSynchronizer $synchronizer;
	/** @var list<int[]> UID chunks passed to findByIds, in call order */
	private array $fetchedChunks = [];

	protected function setUp(): void {
		parent::setUp();
		$this->dbMapper = $this->createMock(DatabaseMessageMapper::class);
		$this->clientFactory = $this->createMock(IMAPClientFactory::class);
		$this->imapMapper = $this->createMock(ImapMessageMapper::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->imapSynchronizer = $this->createMock(Synchronizer::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->performanceLogger = $this->createMock(PerformanceLogger::class);
		$this->performanceLogger->method('startWithLogger')
			->willReturn($this->createStub(PerformanceLoggerTask::class));
		$this->synchronizer = new ImapToDbSynchronizer(
			$this->dbMapper,
			$this->clientFactory,
			$this->imapMapper,
			$this->mailboxMapper,
			$this->createStub(DatabaseMessageMapper::class),
			$this->imapSynchronizer,
			$this->dispatcher,
			$this->performanceLogger,
			$this->createStub(LoggerInterface::class),
			$this->createStub(IMailManager::class),
			$this->createStub(TagMapper::class),
			$this->createStub(NewMessagesClassifier::class),
		);
	}

	public function testInitialSyncGetsSyncTokenFromCacheClient(): void {
		[$account, $mailbox] = $this->createAccountAndMailbox();
		$capability = $this->createMock(Horde_Imap_Client_Data_Capability_Imap::class);
		$capability->method('isEnabled')->with('QRESYNC')->willReturn(false);
		$initialClient = $this->createMock(Horde_Imap_Client_Socket::class);
		$initialClient->method('__get')->with('capability')->willReturn($capability);
		$noCacheClient = $this->createStub(Horde_Imap_Client_Socket::class);
		$initialClient->expects($this->once())
			->method('getSyncToken')
			->with('INBOX')
			->willReturn('dG9rZW5XaXRoSA==');
		$this->clientFactory->expects($this->once())
			->method('getClient')
			->with($account, false)
			->willReturn($noCacheClient);
		$this->dbMapper->method('findHighestUid')->willReturn(null);
		$this->imapMapper->method('findAll')->willReturn([
			'messages' => [],
			'all' => true,
			'total' => 0,
		]);
		$this->mailboxMapper->expects($this->once())
			->method('update')
			->with($this->callback(fn (Mailbox $mb) => $mb->getSyncNewToken() === 'dG9rZW5XaXRoSA=='
					&& $mb->getSyncChangedToken() === 'dG9rZW5XaXRoSA=='
					&& $mb->getSyncVanishedToken() === 'dG9rZW5XaXRoSA=='));
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->isInstanceOf(SynchronizationEvent::class));
		$this->synchronizer->sync(
			$account,
			$initialClient,
			$mailbox,
			$this->createStub(LoggerInterface::class),
		);
	}

	public function testPartialSyncFetchesNewMessagesInAscendingChunks(): void {
		[$account, $mailbox, $client] = $this->createPartialSyncFixture();
		$chunkSize = ImapToDbSynchronizer::PARTIAL_SYNC_CHUNK_SIZE;
		$this->mockImapSync(array_reverse(range(1, 100 + 2 * $chunkSize + 1)), []);
		$this->dbMapper->method('findHighestUid')->willReturn(100);
		$this->recordFetchedChunks($client);
		$this->dbMapper->expects($this->exactly(3))
			->method('insertBulk');
		$this->dispatcher->expects($this->exactly(3))
			->method('dispatch')
			->with(NewMessagesSynchronized::class, $this->isInstanceOf(NewMessagesSynchronized::class));

		$rebuildThreads = $this->synchronizer->sync(
			$account,
			$client,
			$mailbox,
			$this->createStub(LoggerInterface::class),
		);

		$this->assertSame([range(101, 100 + $chunkSize), range(101 + $chunkSize, 100 + 2 * $chunkSize), [101 + 2 * $chunkSize]], $this->fetchedChunks);
		$this->assertTrue($rebuildThreads);
	}

	public function testPartialSyncDoesNotFetchKnownMessagesButRebuildsTheirThreads(): void {
		[$account, $mailbox, $client] = $this->createPartialSyncFixture();
		$this->mockImapSync(range(1, 100), []);
		$this->dbMapper->method('findHighestUid')->willReturn(100);
		$this->imapMapper->expects($this->never())
			->method('findByIds');
		$this->dbMapper->expects($this->never())
			->method('insertBulk');
		$this->dispatcher->expects($this->never())
			->method('dispatch');

		$rebuildThreads = $this->synchronizer->sync(
			$account,
			$client,
			$mailbox,
			$this->createStub(LoggerInterface::class),
		);

		$this->assertTrue($rebuildThreads);
	}

	public function testPartialSyncSkipsNewChunksWithoutMessages(): void {
		[$account, $mailbox, $client] = $this->createPartialSyncFixture();
		$chunkSize = ImapToDbSynchronizer::PARTIAL_SYNC_CHUNK_SIZE;
		$this->mockImapSync(range(1, $chunkSize + 100), []);
		$this->dbMapper->method('findHighestUid')->willReturn(null);
		$this->imapMapper->method('findByIds')
			->willReturnCallback(fn (Horde_Imap_Client_Base $client, string $mailbox, array $uids): array => match ($uids[0]) {
				1 => [],
				$chunkSize + 1 => $this->stubImapMessages($uids),
			});
		$this->dbMapper->expects($this->once())
			->method('insertBulk');
		$this->dispatcher->expects($this->once())
			->method('dispatch');

		$rebuildThreads = $this->synchronizer->sync(
			$account,
			$client,
			$mailbox,
			$this->createStub(LoggerInterface::class),
		);

		$this->assertTrue($rebuildThreads);
	}

	public function testPartialSyncKeepsStoredChunksAndTokenWhenALaterChunkFails(): void {
		[$account, $mailbox, $client] = $this->createPartialSyncFixture();
		$chunkSize = ImapToDbSynchronizer::PARTIAL_SYNC_CHUNK_SIZE;
		$this->mockImapSync(range(1, 2 * $chunkSize), []);
		$this->dbMapper->method('findHighestUid')->willReturn(null);
		$this->imapMapper->method('findByIds')
			->willReturnCallback(fn (Horde_Imap_Client_Base $client, string $mailbox, array $uids): array => match ($uids[0]) {
				1 => $this->stubImapMessages($uids),
				$chunkSize + 1 => throw new Horde_Imap_Client_Exception('connection lost'),
			});
		$this->dbMapper->expects($this->once())
			->method('insertBulk');
		$this->dispatcher->expects($this->once())
			->method('dispatch');
		$this->mailboxMapper->expects($this->never())
			->method('update');

		try {
			$this->synchronizer->sync(
				$account,
				$client,
				$mailbox,
				$this->createStub(LoggerInterface::class),
			);
			$this->fail('Expected a ServiceException');
		} catch (ServiceException) {
		}

		$this->assertSame('token', $mailbox->getSyncNewToken());
	}

	public function testPartialSyncFetchesChangedMessagesInChunks(): void {
		[$account, $mailbox, $client] = $this->createPartialSyncFixture();
		$chunkSize = ImapToDbSynchronizer::PARTIAL_SYNC_CHUNK_SIZE;
		$this->mockImapSync([], range(1, 2 * $chunkSize + 1));
		$this->recordFetchedChunks($client);
		$this->dbMapper->expects($this->exactly(3))
			->method('updateBulk');

		$this->synchronizer->sync(
			$account,
			$client,
			$mailbox,
			$this->createStub(LoggerInterface::class),
		);

		$this->assertSame([range(1, $chunkSize), range($chunkSize + 1, 2 * $chunkSize), [2 * $chunkSize + 1]], $this->fetchedChunks);
	}

	public function testPartialSyncSkipsChangedChunksWithoutMessages(): void {
		[$account, $mailbox, $client] = $this->createPartialSyncFixture();
		$chunkSize = ImapToDbSynchronizer::PARTIAL_SYNC_CHUNK_SIZE;
		$this->mockImapSync([], range(1, $chunkSize + 1));
		$this->imapMapper->method('findByIds')
			->willReturnCallback(fn (Horde_Imap_Client_Base $client, string $mailbox, array $uids): array => match ($uids[0]) {
				1 => [],
				$chunkSize + 1 => $this->stubImapMessages($uids),
			});
		$this->dbMapper->expects($this->once())
			->method('updateBulk');

		$this->synchronizer->sync(
			$account,
			$client,
			$mailbox,
			$this->createStub(LoggerInterface::class),
		);
	}

	public function testPartialSyncKeepsAllTokensWhenAChangedChunkFailsAfterNewMessages(): void {
		[$account, $mailbox, $client] = $this->createPartialSyncFixture();
		$chunkSize = ImapToDbSynchronizer::PARTIAL_SYNC_CHUNK_SIZE;
		$changedStart = $chunkSize + 1;
		$this->mockImapSync(range(1, 10), range($changedStart, $changedStart + $chunkSize));
		$this->dbMapper->method('findHighestUid')->willReturn(null);
		$this->imapMapper->method('findByIds')
			->willReturnCallback(fn (Horde_Imap_Client_Base $client, string $mailbox, array $uids): array => match ($uids[0]) {
				1, $changedStart => $this->stubImapMessages($uids),
				$changedStart + $chunkSize => throw new Horde_Imap_Client_Exception('connection lost'),
			});
		$this->dbMapper->expects($this->once())
			->method('insertBulk');
		$this->dbMapper->expects($this->once())
			->method('updateBulk');
		$this->mailboxMapper->expects($this->never())
			->method('update');

		try {
			$this->synchronizer->sync(
				$account,
				$client,
				$mailbox,
				$this->createStub(LoggerInterface::class),
			);
			$this->fail('Expected a ServiceException');
		} catch (ServiceException) {
		}

		$this->assertSame(['token', 'token', 'token'], [$mailbox->getSyncNewToken(), $mailbox->getSyncChangedToken(), $mailbox->getSyncVanishedToken()]);
	}

	/**
	 * @return array{Account, Mailbox}
	 */
	private function createAccountAndMailbox(): array {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('user');
		$mailbox = new Mailbox();
		$mailbox->setId(100);
		$mailbox->setName('INBOX');
		$mailbox->setAccountId(1);
		$mailbox->setSelectable(true);
		return [new Account($mailAccount), $mailbox];
	}

	/**
	 * @return array{Account, Mailbox, Horde_Imap_Client_Socket&MockObject}
	 */
	private function createPartialSyncFixture(): array {
		[$account, $mailbox] = $this->createAccountAndMailbox();
		$mailbox->setSyncNewToken('token');
		$mailbox->setSyncChangedToken('token');
		$mailbox->setSyncVanishedToken('token');
		$capability = $this->createStub(Horde_Imap_Client_Data_Capability_Imap::class);
		$capability->method('isEnabled')->willReturn(false);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$client->method('__get')->willReturnMap([['capability', $capability]]);
		$client->method('getSyncToken')->willReturn('newToken');
		$this->dbMapper->method('findAllUids')->willReturn([]);
		return [$account, $mailbox, $client];
	}

	/**
	 * @param int[] $newUids
	 * @param int[] $changedUids
	 */
	private function mockImapSync(array $newUids, array $changedUids): void {
		$this->imapSynchronizer->method('sync')
			->willReturn([$newUids, $changedUids, []]);
	}

	private function recordFetchedChunks(Horde_Imap_Client_Socket $client): void {
		$this->imapMapper->method('findByIds')
			->with($client, 'INBOX', $this->isType('array'), 'user')
			->willReturnCallback(function (Horde_Imap_Client_Base $client, string $mailbox, array $uids): array {
				$this->fetchedChunks[] = $uids;
				return $this->stubImapMessages($uids);
			});
	}

	/**
	 * @param int[] $uids
	 * @return IMAPMessage[]
	 */
	private function stubImapMessages(array $uids): array {
		return array_fill(0, count($uids), $this->createStub(IMAPMessage::class));
	}
}
