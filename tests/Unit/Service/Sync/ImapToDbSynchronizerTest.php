<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Sync;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client;
use Horde_Imap_Client_Base;
use Horde_Imap_Client_Data_Capability_Imap;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DatabaseMessageMapper;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Events\SynchronizationEvent;
use OCA\Mail\Exception\MailboxDoesNotSupportModSequencesException;
use OCA\Mail\Exception\MailboxLockedException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\UidValidityChangedException;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\IMAP\Sync\Synchronizer;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\Classification\NewMessagesClassifier;
use OCA\Mail\Service\MailManager;
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

final class ImapToDbSynchronizerTest extends TestCase {
	private DatabaseMessageMapper&MockObject $dbMapper;
	private ProtocolFactory&MockObject $protocolFactory;
	private ImapMessageMapper&MockObject $imapMapper;
	private MailboxMapper&MockObject $mailboxMapper;
	private Synchronizer&MockObject $imapSynchronizer;
	private IEventDispatcher&MockObject $dispatcher;
	private PerformanceLogger&MockObject $performanceLogger;
	private MailManager&MockObject $mailManager;
	private ImapToDbSynchronizer $synchronizer;
	/** @var list<int[]> UID chunks passed to findByIds, in call order */
	private array $fetchedChunks = [];
	/** @var list<array{bool, int}> permflags flag and message count of each updateBulk call */
	private array $bulkUpdates = [];

	protected function setUp(): void {
		parent::setUp();
		$this->dbMapper = $this->createMock(DatabaseMessageMapper::class);
		$this->protocolFactory = $this->createMock(ProtocolFactory::class);
		$this->imapMapper = $this->createMock(ImapMessageMapper::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->imapSynchronizer = $this->createMock(Synchronizer::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->performanceLogger = $this->createMock(PerformanceLogger::class);
		$this->performanceLogger->method('startWithLogger')
			->willReturn($this->createStub(PerformanceLoggerTask::class));
		$this->mailManager = $this->createMock(MailManager::class);
		$this->synchronizer = new ImapToDbSynchronizer(
			$this->dbMapper,
			$this->protocolFactory,
			$this->imapMapper,
			$this->mailboxMapper,
			$this->createStub(DatabaseMessageMapper::class),
			$this->imapSynchronizer,
			$this->dispatcher,
			$this->performanceLogger,
			$this->createStub(LoggerInterface::class),
			$this->mailManager,
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
		$this->protocolFactory->expects($this->once())
			->method('imapClient')
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

	public function testInitialSyncRefreshesTheLocksBeforeEachChunk(): void {
		[$account, $mailbox] = $this->createAccountAndMailbox();
		$capability = $this->createStub(Horde_Imap_Client_Data_Capability_Imap::class);
		$client = $this->createStub(Horde_Imap_Client_Socket::class);
		$client->method('__get')->willReturnMap([['capability', $capability]]);
		$this->protocolFactory->method('imapClient')
			->willReturn($this->createStub(Horde_Imap_Client_Socket::class));
		$this->imapMapper->method('findAll')
			->willReturn(['messages' => $this->stubImapMessages(range(1, 501)), 'all' => true, 'total' => 501]);
		$this->mailboxMapper->expects($this->exactly(3))
			->method('refreshSyncLocks')
			->with($mailbox, true, true, true);

		$this->synchronizer->sync($account, $client, $mailbox, $this->createStub(LoggerInterface::class));
	}

	public function testPartialSyncRefreshesTheLocksBeforeEachWriteAndTheTokens(): void {
		[$account, $mailbox, $client] = $this->createPartialSyncFixture();
		$chunkSize = ImapToDbSynchronizer::PARTIAL_SYNC_CHUNK_SIZE;
		$this->mockImapSync(range(1, $chunkSize + 1), [1000]);
		$this->recordFetchedChunks($client);
		$steps = [];
		$this->mailboxMapper->expects($this->exactly(4))
			->method('refreshSyncLocks')
			->with($mailbox, true, true, true)
			->willReturnCallback(function () use (&$steps): void {
				$steps[] = 'refresh';
			});
		$this->dbMapper->method('insertBulk')
			->willReturnCallback(function () use (&$steps): void {
				$steps[] = 'insert';
			});
		$this->dbMapper->method('updateBulk')
			->willReturnCallback(function (Account $account, bool $permflagsEnabled, Message ...$messages) use (&$steps): array {
				$steps[] = 'update';
				return $messages;
			});
		$this->mailboxMapper->method('update')
			->willReturnCallback(function (Mailbox $mailbox) use (&$steps): Mailbox {
				$steps[] = 'save tokens';
				return $mailbox;
			});

		$this->synchronizer->sync($account, $client, $mailbox, $this->createStub(LoggerInterface::class));

		$this->assertSame(['refresh', 'insert', 'refresh', 'insert', 'refresh', 'update', 'refresh', 'save tokens'], $steps);
	}

	public function testPartialSyncOfChangedMessagesRefreshesOnlyItsLock(): void {
		[$account, $mailbox, $client] = $this->createPartialSyncFixture();
		$this->mockImapSync([], [5]);
		$this->recordFetchedChunks($client);
		$this->mailboxMapper->expects($this->exactly(2))
			->method('refreshSyncLocks')
			->with($mailbox, false, true, false);

		$this->synchronizer->sync($account, $client, $mailbox, $this->createStub(LoggerInterface::class), Horde_Imap_Client::SYNC_FLAGSUIDS);
	}

	public function testPartialSyncRefreshesTheLocksBeforeDeletingVanishedMessages(): void {
		[$account, $mailbox, $client] = $this->createPartialSyncFixture();
		$this->mockImapSync([], [], vanishedUids: [5, 6]);
		$steps = [];
		$this->recordLockSteps($steps);

		$this->synchronizer->sync($account, $client, $mailbox, $this->createStub(LoggerInterface::class));

		$this->assertSame(['refresh', 'delete 5,6', 'refresh'], $steps);
	}

	public function testForcedSyncRefreshesAllLocksWhateverTheCriteria(): void {
		[$account, $mailbox] = $this->createAccountAndMailbox();
		$capability = $this->createStub(Horde_Imap_Client_Data_Capability_Imap::class);
		$client = $this->createStub(Horde_Imap_Client_Socket::class);
		$client->method('__get')->willReturnMap([['capability', $capability]]);
		$this->protocolFactory->method('imapClient')
			->willReturn($this->createStub(Horde_Imap_Client_Socket::class));
		$this->imapMapper->method('findAll')->willReturn(['messages' => [], 'all' => true, 'total' => 0]);
		$this->mailboxMapper->expects($this->once())->method('lockForNewSync');
		$this->mailboxMapper->expects($this->once())->method('lockForChangeSync');
		$this->mailboxMapper->expects($this->once())->method('lockForVanishedSync');
		$this->mailboxMapper->expects($this->once())
			->method('refreshSyncLocks')
			->with($mailbox, true, true, true);

		$this->synchronizer->sync($account, $client, $mailbox, $this->createStub(LoggerInterface::class), Horde_Imap_Client::SYNC_FLAGSUIDS, force: true);
	}

	public function testUidValidityChangeRefreshesTheLocksBeforeClearingTheCache(): void {
		[$account, $mailbox, $client] = $this->createPartialSyncFixture();
		$this->imapSynchronizer->method('sync')->willThrowException(new UidValidityChangedException());
		$this->protocolFactory->method('imapClient')
			->willReturn($this->createStub(Horde_Imap_Client_Socket::class));
		$this->imapMapper->method('findAll')->willReturn(['messages' => [], 'all' => true, 'total' => 0]);
		$steps = [];
		$this->recordLockSteps($steps);

		$this->synchronizer->sync($account, $client, $mailbox, $this->createStub(LoggerInterface::class));

		$this->assertSame(['refresh', 'clear cache', 'refresh'], $steps);
	}

	public function testMissingModSequencesRefreshTheLocksBeforeClearingTheCache(): void {
		[$account, $mailbox, $client] = $this->createPartialSyncFixture();
		$this->imapSynchronizer->method('sync')->willThrowException(new MailboxDoesNotSupportModSequencesException('no mod-sequences'));
		$this->protocolFactory->method('imapClient')
			->willReturn($this->createStub(Horde_Imap_Client_Socket::class));
		$this->imapMapper->method('findAll')->willReturn(['messages' => [], 'all' => true, 'total' => 0]);
		$steps = [];
		$this->recordLockSteps($steps);

		$this->synchronizer->sync($account, $client, $mailbox, $this->createStub(LoggerInterface::class));

		$this->assertSame(['refresh', 'clear cache', 'refresh'], $steps);
	}

	public function testSyncAccountSkipsMailboxesThatAnotherProcessIsSyncing(): void {
		[$account, , $client] = $this->createPartialSyncFixture();
		$locked = $this->createBackgroundMailbox(101);
		$free = $this->createBackgroundMailbox(102);
		$this->protocolFactory->method('imapClient')->willReturn($client);
		$this->mailboxMapper->method('findAll')->willReturn([$locked, $free]);
		$this->mailboxMapper->method('lockForNewSync')
			->willReturnCallback(static function (Mailbox $mailbox) use ($locked): void {
				if ($mailbox === $locked) {
					throw MailboxLockedException::from($mailbox);
				}
			});
		$this->mockImapSync([], []);
		$this->mailboxMapper->expects($this->once())
			->method('update')
			->with($free);
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->isInstanceOf(SynchronizationEvent::class));

		$this->synchronizer->syncAccount($account, $this->createStub(LoggerInterface::class));
	}

	public function testRepairSyncRefreshesTheLockBeforeDeleting(): void {
		[$account, $mailbox] = $this->createAccountAndMailbox();
		$this->stubRepairClient($account, [5]);
		$this->dbMapper->method('findAllUids')->willReturn([5, 6]);
		$this->mailboxMapper->method('lockForVanishedSync')
			->willReturnCallback(static fn (Mailbox $mailbox) => $mailbox->setSyncVanishedLock(1000));
		$steps = [];
		$this->recordLockSteps($steps);
		$this->mailboxMapper->expects($this->once())->method('unlockFromVanishedSync');

		$this->synchronizer->repairSync($account, $mailbox, $this->createStub(LoggerInterface::class));

		$this->assertSame(['refresh', 'delete 5'], $steps);
	}

	public function testRepairSyncStopsWhenAnotherProcessTookOverTheLock(): void {
		[$account, $mailbox] = $this->createAccountAndMailbox();
		$this->stubRepairClient($account, [5]);
		$this->dbMapper->method('findAllUids')->willReturn([5, 6]);
		$this->mailboxMapper->method('lockForVanishedSync')
			->willReturnCallback(static fn (Mailbox $mailbox) => $mailbox->setSyncVanishedLock(1000));
		$this->mailboxMapper->method('refreshSyncLocks')
			->willReturnCallback(static function (Mailbox $mailbox): void {
				$mailbox->setSyncVanishedLock(null);
				throw MailboxLockedException::from($mailbox);
			});
		$this->dbMapper->expects($this->never())->method('deleteByUid');
		$this->mailboxMapper->expects($this->never())->method('unlockFromVanishedSync');

		$this->expectException(MailboxLockedException::class);
		$this->synchronizer->repairSync($account, $mailbox, $this->createStub(LoggerInterface::class));
	}

	public function testPartialSyncStopsWhenAnotherProcessTookOverTheLocks(): void {
		[$account, $mailbox, $client] = $this->createPartialSyncFixture();
		$chunkSize = ImapToDbSynchronizer::PARTIAL_SYNC_CHUNK_SIZE;
		$this->mockImapSync(range(1, 2 * $chunkSize), []);
		$this->dbMapper->method('findHighestUid')->willReturn(null);
		$this->recordFetchedChunks($client);
		$mailbox->setSyncNewLock(1000);
		$mailbox->setSyncChangedLock(1000);
		$mailbox->setSyncVanishedLock(1000);
		$this->mailboxMapper->method('refreshSyncLocks')
			->willReturnCallback(function (Mailbox $mailbox): void {
				if (count($this->fetchedChunks) > 1) {
					$mailbox->setSyncNewLock(null);
					$mailbox->setSyncChangedLock(null);
					$mailbox->setSyncVanishedLock(null);
					throw MailboxLockedException::from($mailbox);
				}
			});
		$this->dbMapper->expects($this->once())
			->method('insertBulk');
		$this->mailboxMapper->expects($this->never())
			->method('update');
		$this->mailboxMapper->expects($this->never())
			->method('unlockFromNewSync');
		$this->mailboxMapper->expects($this->never())
			->method('unlockFromChangedSync');
		$this->mailboxMapper->expects($this->never())
			->method('unlockFromVanishedSync');

		$this->expectException(MailboxLockedException::class);
		$this->synchronizer->sync($account, $client, $mailbox, $this->createStub(LoggerInterface::class));
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

	public function testPartialSyncDoesNotFetchNewMessagesAgainAsChanged(): void {
		[$account, $mailbox, $client] = $this->createPartialSyncFixture(qresync: true);
		$this->mockImapSync(range(11, 30), [5, ...range(11, 30)], hasQresync: true);
		$this->dbMapper->method('findHighestUid')->willReturn(10);
		$this->mailManager->method('isPermflagsEnabled')->willReturn(true);
		$this->recordFetchedChunks($client);
		$this->recordBulkUpdates();
		$this->dbMapper->expects($this->once())
			->method('insertBulk');

		$this->synchronizer->sync(
			$account,
			$client,
			$mailbox,
			$this->createStub(LoggerInterface::class),
		);

		$this->assertSame([range(11, 30), [5]], $this->fetchedChunks);
		$this->assertSame([[true, 20], [true, 1]], $this->bulkUpdates);
	}

	public function testPartialSyncUpdatesNewMessagesStoredByAnEarlierRun(): void {
		[$account, $mailbox, $client] = $this->createPartialSyncFixture(qresync: true);
		$this->mockImapSync(range(5, 30), range(5, 30), hasQresync: true);
		$this->dbMapper->method('findHighestUid')->willReturn(10);
		$this->recordFetchedChunks($client);
		$this->recordBulkUpdates();
		$this->dbMapper->expects($this->once())
			->method('insertBulk');

		$this->synchronizer->sync(
			$account,
			$client,
			$mailbox,
			$this->createStub(LoggerInterface::class),
		);

		$this->assertSame([range(11, 30), range(5, 10)], $this->fetchedChunks);
		$this->assertSame([[false, 20], [false, 6]], $this->bulkUpdates);
	}

	public function testPartialSyncWithoutQresyncOnlyUpdatesChangedMessages(): void {
		[$account, $mailbox, $client] = $this->createPartialSyncFixture();
		$this->mockImapSync(range(11, 30), [5]);
		$this->dbMapper->method('findHighestUid')->willReturn(10);
		$this->recordFetchedChunks($client);
		$this->recordBulkUpdates();

		$this->synchronizer->sync(
			$account,
			$client,
			$mailbox,
			$this->createStub(LoggerInterface::class),
		);

		$this->assertSame([[false, 1]], $this->bulkUpdates);
	}

	public function testPartialSyncOfChangedMessagesOnly(): void {
		[$account, $mailbox, $client] = $this->createPartialSyncFixture();
		$this->mockImapSync([], [5, 6]);
		$this->recordFetchedChunks($client);
		$this->recordBulkUpdates();
		$this->dbMapper->expects($this->never())
			->method('insertBulk');

		$this->synchronizer->sync(
			$account,
			$client,
			$mailbox,
			$this->createStub(LoggerInterface::class),
			Horde_Imap_Client::SYNC_FLAGSUIDS,
		);

		$this->assertSame([[5, 6]], $this->fetchedChunks);
		$this->assertSame([[false, 2]], $this->bulkUpdates);
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
	private function createPartialSyncFixture(bool $qresync = false): array {
		[$account, $mailbox] = $this->createAccountAndMailbox();
		$mailbox->setSyncNewToken('token');
		$mailbox->setSyncChangedToken('token');
		$mailbox->setSyncVanishedToken('token');
		$capability = $this->createStub(Horde_Imap_Client_Data_Capability_Imap::class);
		$capability->method('isEnabled')->willReturnCallback(static fn (string $extension): bool => $qresync && $extension === 'QRESYNC');
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
	/**
	 * @param int[] $newUids
	 * @param int[] $changedUids
	 * @param int[] $vanishedUids
	 */
	private function mockImapSync(array $newUids, array $changedUids, bool $hasQresync = false, array $vanishedUids = []): void {
		$this->imapSynchronizer->method('sync')
			->with($this->anything(), $this->anything(), $hasQresync, $this->anything(), $this->anything())
			->willReturn([$newUids, $changedUids, $vanishedUids]);
	}

	/**
	 * Records refreshes, cache clears and deletions in $steps
	 *
	 * @param list<string> $steps
	 */
	private function recordLockSteps(array &$steps): void {
		$this->mailboxMapper->method('refreshSyncLocks')
			->willReturnCallback(function () use (&$steps): void {
				$steps[] = 'refresh';
			});
		$this->dbMapper->method('deleteAll')
			->willReturnCallback(function () use (&$steps): void {
				$steps[] = 'clear cache';
			});
		$this->dbMapper->method('deleteByUid')
			->willReturnCallback(function (Mailbox $mailbox, int ...$uids) use (&$steps): void {
				$steps[] = 'delete ' . implode(',', $uids);
			});
	}

	/**
	 * @param int[] $vanishedUids
	 */
	private function stubRepairClient(Account $account, array $vanishedUids): void {
		$client = $this->createStub(Horde_Imap_Client_Socket::class);
		$client->method('vanished')->willReturn(new Horde_Imap_Client_Ids($vanishedUids));
		$this->protocolFactory->method('imapClient')
			->with($account, false)
			->willReturn($client);
	}

	private function createBackgroundMailbox(int $id): Mailbox {
		$mailbox = new Mailbox();
		$mailbox->setId($id);
		$mailbox->setName("Folder $id");
		$mailbox->setAccountId(1);
		$mailbox->setSelectable(true);
		$mailbox->setSyncInBackground(true);
		$mailbox->setSyncNewToken('token');
		$mailbox->setSyncChangedToken('token');
		$mailbox->setSyncVanishedToken('token');
		return $mailbox;
	}

	private function recordBulkUpdates(): void {
		$this->dbMapper->method('updateBulk')
			->willReturnCallback(function (Account $account, bool $permflagsEnabled, Message ...$messages): array {
				$this->bulkUpdates[] = [$permflagsEnabled, count($messages)];
				return $messages;
			});
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
