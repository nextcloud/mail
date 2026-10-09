<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Sync;

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailboxConnector;
use OCA\Mail\Contracts\IMessageConnector;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\MailboxNotCachedException;
use OCA\Mail\IMAP\MailboxStats;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\IMAP\PreviewEnhancer;
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\Search\FilterStringParser;
use OCA\Mail\Service\Search\SearchQuery;
use OCA\Mail\Service\Sync\ImapToDbSynchronizer;
use OCA\Mail\Service\Sync\SyncService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SyncServiceTest extends TestCase {

	private ProtocolFactory&MockObject $protocolFactory;

	private IMailboxConnector&MockObject $mailboxConnector;

	private IMessageConnector&MockObject $messageConnector;

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

		$this->protocolFactory = $this->createMock(ProtocolFactory::class);
		$this->mailboxConnector = $this->createMock(IMailboxConnector::class);
		$this->messageConnector = $this->createMock(IMessageConnector::class);
		$this->synchronizer = $this->createMock(ImapToDbSynchronizer::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->mailboxSync = $this->createMock(MailboxSync::class);

		$this->protocolFactory->method('mailboxConnector')
			->willReturn($this->mailboxConnector);
		$this->protocolFactory->method('messageConnector')
			->willReturn($this->messageConnector);

		$this->syncService = new SyncService(
			$this->protocolFactory,
			$this->synchronizer,
			$this->createStub(FilterStringParser::class),
			$this->messageMapper,
			$this->createStub(PreviewEnhancer::class),
			$this->createStub(\Psr\Log\LoggerInterface::class),
			$this->mailboxSync
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
		$this->messageMapper
			->method('findUidsForIds')
			->with($mailbox, [])
			->willReturn([]);
		$this->mailboxConnector->expects($this->once())
			->method('syncOne')
			->with($account, $mailbox);
		$this->messageConnector->expects($this->once())
			->method('syncMailbox')
			->with(
				$account,
				$mailbox,
				$this->isInstanceOf(\Psr\Log\LoggerInterface::class),
				0,
				[],
				true
			)
			->willReturn(new \OCA\Mail\Protocol\SyncResult());

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

	public function testSyncWithoutKnownIdsReturnsOnePageOfNewMessages(): void {
		$account = $this->createMock(Account::class);
		$account->method('getUserId')->willReturn('user');
		$mailbox = new Mailbox();
		$mailbox->setMessages(33000);
		$mailbox->setUnseen(24000);
		$this->messageConnector->method('syncMailbox')
			->willReturn(new \OCA\Mail\Protocol\SyncResult());
		$this->messageMapper->expects($this->never())
			->method('findAllIds');
		$this->messageMapper->expects($this->never())
			->method('findNewIds');
		$this->messageMapper->expects($this->once())
			->method('findIdsByQuery')
			->with($mailbox, $this->isInstanceOf(SearchQuery::class), 'DESC', 20)
			->willReturn([3, 2, 1]);
		$this->messageMapper->expects($this->exactly(2))
			->method('findByMailboxAndIds')
			->willReturnCallback(fn (Mailbox $mb, string $userId, array $ids) => array_map(static function (int $id): Message {
				$message = new Message();
				$message->setId($id);
				return $message;
			}, $ids));
		$previewEnhancer = $this->createMock(PreviewEnhancer::class);
		$previewEnhancer->method('process')
			->willReturnArgument(2);
		$syncService = new SyncService(
			$this->protocolFactory,
			$this->synchronizer,
			$this->createStub(FilterStringParser::class),
			$this->messageMapper,
			$previewEnhancer,
			$this->createStub(\Psr\Log\LoggerInterface::class),
			$this->mailboxSync
		);

		$response = $syncService->syncMailbox(
			$account,
			$mailbox,
			0,
			false,
			null,
			[]
		);

		$this->assertSame([3, 2, 1], array_map(static fn (Message $message) => $message->getId(), $response->getNewMessages()));
		$this->assertSame([], $response->getChangedMessages());
	}
}
