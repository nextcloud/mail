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
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\IMAP\Sync\Request;
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\IMAP\Sync\Synchronizer;
use PHPUnit\Framework\MockObject\MockObject;
use function range;

class SynchronizerTest extends TestCase {
	/** @var MessageMapper|MockObject */
	private $mapper;

	/** @var Synchronizer */
	private $synchronizer;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(MessageMapper::class);

		$this->synchronizer = new Synchronizer($this->mapper);
	}

	public function testSyncWithQresync(): void {
		$imapClient = $this->createMock(Horde_Imap_Client_Base::class);
		$request = $this->createMock(Request::class);
		$request->expects($this->any())
			->method('getMailbox')
			->willReturn('inbox');
		$request->expects($this->once())
			->method('getToken')
			->willReturn('123456');
		$request->expects($this->exactly(3))
			->method('getId')
			->willReturn('abcdef');
		$hordeSync = $this->createMock(Horde_Imap_Client_Data_Sync::class);
		$imapClient->expects($this->once())
			->method('sync')
			->with($this->equalTo(new Horde_Imap_Client_Mailbox('inbox')), $this->equalTo('123456'))
			->willReturn($hordeSync);
		$newMessages = [];
		$changedMessages = [];
		$vanishedMessageUids = [4, 5];
		$hordeSync->expects($this->exactly(3))
			->method('__get')
			->willReturnMap([
				['newmsgsuids', new Horde_Imap_Client_Ids($newMessages)],
				['flagsuids', new Horde_Imap_Client_Ids($changedMessages)],
				['vanisheduids', new Horde_Imap_Client_Ids($vanishedMessageUids)],
			]);
		$expected = new Response($newMessages, $changedMessages, $vanishedMessageUids);

		$newResponse = $this->synchronizer->sync(
			$imapClient,
			$request,
			'user',
			true,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS,
		);
		$changedResponse = $this->synchronizer->sync(
			$imapClient,
			$request,
			'user',
			true,
			Horde_Imap_Client::SYNC_FLAGSUIDS,
		);
		$vanishedResponse = $this->synchronizer->sync(
			$imapClient,
			$request,
			'user',
			true,
			Horde_Imap_Client::SYNC_VANISHEDUIDS
		);

		$this->assertEquals($expected, $newResponse);
		$this->assertEquals($expected, $changedResponse);
		$this->assertEquals($expected, $vanishedResponse);
	}

	public function testSyncChunked(): void {
		$imapClient = $this->createMock(Horde_Imap_Client_Base::class);
		$request = $this->createMock(Request::class);
		$request->method('getMailbox')
			->willReturn('inbox');
		$request->method('getToken')
			->willReturn('123456');
		$request->method('getUids')
			->willReturn(range(1, 8000, 2)); // 19444 bytes
		$hordeSync = $this->createMock(Horde_Imap_Client_Data_Sync::class);
		$imapClient->expects($this->exactly(3))
			->method('sync')
			->with($this->equalTo(new Horde_Imap_Client_Mailbox('inbox')), $this->equalTo('123456'))
			->willReturn($hordeSync);
		$newMessages = $changedMessages = $vanishedMessageUids = [];
		$hordeSync->expects($this->any())
			->method('__get')
			->willReturn(new Horde_Imap_Client_Ids([]));
		$expected = new Response($newMessages, $changedMessages, $vanishedMessageUids);

		$response = $this->synchronizer->sync(
			$imapClient,
			$request,
			'user',
			false,
			Horde_Imap_Client::SYNC_VANISHEDUIDS
		);

		$this->assertEquals($expected, $response);
	}
}
