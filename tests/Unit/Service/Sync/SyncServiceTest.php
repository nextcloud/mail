<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Sync;

use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\MailboxNotCachedException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MailboxStats;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\IMAP\PreviewEnhancer;
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\Service\Search\FilterStringParser;
use OCA\Mail\Service\Sync\ImapToDbSynchronizer;
use OCA\Mail\Service\Sync\SyncService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SyncServiceTest extends TestCase {

	private IMAPClientFactory&MockObject $clientFactory;
	private Horde_Imap_Client_Socket&MockObject $client;

	/** @var ImapToDbSynchronizer */
	private $synchronizer;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var LoggerInterface */
	private $loggerInterface;

	/** @var MailboxSync */
	private $mailboxSync;

	/** @var SyncService */
	private $syncService;

	protected function setUp(): void {
		parent::setUp();

		$this->clientFactory = $this->createMock(IMAPClientFactory::class);
		$this->client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->synchronizer = $this->createMock(ImapToDbSynchronizer::class);
		$filterStringParser = $this->createMock(FilterStringParser::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$previewEnhancer = $this->createMock(PreviewEnhancer::class);
		$this->loggerInterface = $this->createMock(LoggerInterface::class);
		$this->mailboxSync = $this->createMock(MailboxSync::class);

		$this->syncService = new SyncService(
			$this->clientFactory,
			$this->synchronizer,
			$filterStringParser,
			$this->messageMapper,
			$previewEnhancer,
			$this->loggerInterface,
			$this->mailboxSync
		);
	}

	public function testPartialSyncOnUncachedMailbox(): void {
		$account = $this->createMock(Account::class);
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
		$this->clientFactory
			->method('getClient')
			->with($account)
			->willReturn($this->client);
		$this->messageMapper
			->method('findUidsForIds')
			->with($mailbox, [])
			->willReturn([]);
		$this->synchronizer->expects($this->once())
			->method('sync')
			->with(
				$account,
				$this->client,
				$mailbox,
				$this->loggerInterface,
				0,
				[],
				true
			);
		$this->mailboxSync->expects($this->once())
			->method('syncStats')
			->with($this->client, $mailbox);

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
}
