<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\IMAP\Sync;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client;
use Horde_Imap_Client_Base;
use Horde_Imap_Client_Data_Sync;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Mailbox;
use OCA\Mail\IMAP\Sync\Request;
use OCA\Mail\IMAP\Sync\Synchronizer;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use function range;

final class SynchronizerTest extends TestCase {
	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var Synchronizer */
	private $synchronizer;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);

		$this->synchronizer = new Synchronizer();
	}

	public function testSyncWithQresync(): void {
		$imapClient = $this->createMock(Horde_Imap_Client_Base::class);
		$request = new Request('abcdef', 'inbox', '123456', []);
		$hordeSync = $this->createMock(Horde_Imap_Client_Data_Sync::class);
		$imapClient->expects($this->once())
			->method('sync')
			->with(new Horde_Imap_Client_Mailbox('inbox'), '123456')
			->willReturn($hordeSync);
		$hordeSync->expects($this->exactly(3))
			->method('__get')
			->willReturnMap([
				['newmsgsuids', new Horde_Imap_Client_Ids([1, 2])],
				['flagsuids', new Horde_Imap_Client_Ids([3])],
				['vanisheduids', new Horde_Imap_Client_Ids([4, 5])],
			]);
		$expected = [[1, 2], [3], [4, 5]];

		$newResponse = $this->synchronizer->sync(
			$imapClient,
			$request,
			true,
			$this->logger,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS,
		);
		$changedResponse = $this->synchronizer->sync(
			$imapClient,
			$request,
			true,
			$this->logger,
			Horde_Imap_Client::SYNC_FLAGSUIDS,
		);
		$vanishedResponse = $this->synchronizer->sync(
			$imapClient,
			$request,
			true,
			$this->logger,
			Horde_Imap_Client::SYNC_VANISHEDUIDS
		);

		$this->assertSame($expected, $newResponse);
		$this->assertSame($expected, $changedResponse);
		$this->assertSame($expected, $vanishedResponse);
	}

	public function testSyncNewMessagesExpandsUidRanges(): void {
		$imapClient = $this->createMock(Horde_Imap_Client_Base::class);
		$request = new Request('abcdef', 'inbox', '123456', []);
		$hordeSync = $this->createMock(Horde_Imap_Client_Data_Sync::class);
		$imapClient->expects($this->once())
			->method('sync')
			->with(new Horde_Imap_Client_Mailbox('inbox'), '123456', ['criteria' => Horde_Imap_Client::SYNC_NEWMSGSUIDS])
			->willReturn($hordeSync);
		$hordeSync->expects($this->atLeast(1))->method('__get')
			->willReturnMap([
				['newmsgsuids', new Horde_Imap_Client_Ids('7:9')],
			]);

		$response = $this->synchronizer->sync(
			$imapClient,
			$request,
			false,
			$this->logger,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS,
		);

		$this->assertSame([[7, 8, 9], [], []], $response);
	}

	public function testSyncChunked(): void {
		$imapClient = $this->createMock(Horde_Imap_Client_Base::class);
		$request = new Request(
			'abcdef',
			'inbox',
			'123456',
			range(1, 8000, 2), // 19444 bytes
		);
		$hordeSync = $this->createMock(Horde_Imap_Client_Data_Sync::class);
		$imapClient->expects($this->exactly(3))
			->method('sync')
			->with(new Horde_Imap_Client_Mailbox('inbox'), '123456')
			->willReturn($hordeSync);
		$hordeSync->expects($this->any())
			->method('__get')
			->willReturn(new Horde_Imap_Client_Ids([]));

		$response = $this->synchronizer->sync(
			$imapClient,
			$request,
			false,
			$this->logger,
			Horde_Imap_Client::SYNC_VANISHEDUIDS
		);

		$this->assertSame([[], [], []], $response);
	}
}
