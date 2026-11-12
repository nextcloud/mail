<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Sync;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Data_Capability_Imap;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DatabaseMessageMapper;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Events\SynchronizationEvent;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\IMAP\Sync\Response;
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

class ImapToDbSynchronizerTest extends TestCase {
	private DatabaseMessageMapper&MockObject $dbMapper;
	private ProtocolFactory&MockObject $protocolFactory;
	private ImapMessageMapper&MockObject $imapMapper;
	private MailboxMapper&MockObject $mailboxMapper;
	private IEventDispatcher&MockObject $dispatcher;
	private PerformanceLogger&MockObject $performanceLogger;
	private Synchronizer&MockObject $imapSynchronizer;
	private ImapToDbSynchronizer $synchronizer;

	protected function setUp(): void {
		parent::setUp();
		$this->dbMapper = $this->createMock(DatabaseMessageMapper::class);
		$this->protocolFactory = $this->createMock(ProtocolFactory::class);
		$this->imapMapper = $this->createMock(ImapMessageMapper::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->performanceLogger = $this->createMock(PerformanceLogger::class);
		$this->performanceLogger->method('startWithLogger')
			->willReturn($this->createStub(PerformanceLoggerTask::class));
		$this->imapSynchronizer = $this->createMock(Synchronizer::class);
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
			$this->createStub(MailManager::class),
			$this->createStub(TagMapper::class),
			$this->createStub(NewMessagesClassifier::class),
		);
	}

	public function testInitialSyncGetsSyncTokenFromCacheClient(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('user');
		$account = new Account($mailAccount);
		$mailbox = new Mailbox();
		$mailbox->setId(100);
		$mailbox->setName('INBOX');
		$mailbox->setAccountId(1);
		$mailbox->setSelectable(true);
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

	private function cachedMailbox(): Mailbox {
		$mailbox = new Mailbox();
		$mailbox->setId(100);
		$mailbox->setName('INBOX');
		$mailbox->setAccountId(1);
		$mailbox->setSelectable(true);
		$mailbox->setSyncNewToken('old');
		$mailbox->setSyncChangedToken('old');
		$mailbox->setSyncVanishedToken('old');
		return $mailbox;
	}

	private function account(): Account {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('user');
		return new Account($mailAccount);
	}

	private function client(): Horde_Imap_Client_Socket&MockObject {
		$capability = $this->createMock(Horde_Imap_Client_Data_Capability_Imap::class);
		$capability->method('isEnabled')->with('QRESYNC')->willReturn(false);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$client->method('__get')->with('capability')->willReturn($capability);
		return $client;
	}

	private function imapMessage(int $uid): IMAPMessage&MockObject {
		$message = $this->createMock(IMAPMessage::class);
		$message->method('getUid')->willReturn($uid);
		$dbMessage = new Message();
		$dbMessage->setUid($uid);
		$message->method('toDbMessage')->willReturn($dbMessage);
		return $message;
	}

	public function testPartialSyncStoresTheTokenTakenBeforeTheSearch(): void {
		$calls = [];
		$client = $this->client();
		$client->expects($this->once())
			->method('getSyncToken')
			->with('INBOX')
			->willReturnCallback(static function () use (&$calls) {
				$calls[] = 'token';
				return 'before';
			});
		$this->imapSynchronizer->method('sync')
			->willReturnCallback(static function () use (&$calls) {
				// A message arriving now is above the token taken before
				$calls[] = 'sync';
				return new Response([], [], []);
			});
		$this->dbMapper->method('findAllUids')->willReturn([]);
		$this->dbMapper->method('findCachedUids')->willReturn([]);
		$this->mailboxMapper->expects($this->once())
			->method('update')
			->with($this->callback(fn (Mailbox $mb) => $mb->getSyncNewToken() === 'before'
				&& $mb->getSyncChangedToken() === 'before'
				&& $mb->getSyncVanishedToken() === 'before'));

		$this->synchronizer->sync(
			$this->account(),
			$client,
			$this->cachedMailbox(),
			$this->createStub(LoggerInterface::class),
		);

		$this->assertSame(['token', 'sync', 'sync', 'sync'], $calls);
	}

	public function testPartialSyncCachesAMissingMessageBelowTheHighestCachedUid(): void {
		$client = $this->client();
		$client->method('getSyncToken')->willReturn('before');
		// 30999 is cached, 30998 arrived while the previous sync ran and was skipped
		$this->imapSynchronizer->method('sync')
			->willReturnOnConsecutiveCalls(
				new Response([$this->imapMessage(30998), $this->imapMessage(30999)], [], []),
				new Response([], [], []),
				new Response([], [], []),
			);
		$this->dbMapper->method('findAllUids')->willReturn([30996, 30997, 30999]);
		$this->dbMapper->expects($this->once())
			->method('findCachedUids')
			->with($this->isInstanceOf(Mailbox::class), [30998, 30999])
			->willReturn([30999]);
		$this->dbMapper->expects($this->once())
			->method('insertBulk')
			->with(
				$this->isInstanceOf(Account::class),
				$this->callback(fn (Message $message) => $message->getUid() === 30998),
			);

		$this->synchronizer->sync(
			$this->account(),
			$client,
			$this->cachedMailbox(),
			$this->createStub(LoggerInterface::class),
		);
	}
}
