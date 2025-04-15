<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Service\MessageOperationService;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class MessageOperationServiceTest extends TestCase {

	private MessageOperationService $service;
	private IMAPClientFactory&MockObject $clientFactory;
	private MailAccountMapper&MockObject $accountMapper;
	private MailboxMapper&MockObject $mailboxMapper;
	private MessageMapper&MockObject $messageMapper;
	private MailManager&MockObject $mailManager;
	private ImapMessageMapper&MockObject $imapMessageMapper;
	
	protected function setUp(): void {
		parent::setUp();

		$this->clientFactory = $this->createMock(IMAPClientFactory::class);
		$this->accountMapper = $this->createMock(MailAccountMapper::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->mailManager = $this->createMock(MailManager::class);
		$this->imapMessageMapper = $this->createMock(ImapMessageMapper::class);

		$this->service = new MessageOperationService(
			$this->clientFactory,
			$this->accountMapper,
			$this->mailboxMapper,
			$this->messageMapper,
			$this->mailManager,
			$this->imapMessageMapper,
		);
	}

	public function testGroupByMailbox() {
		// construct message metadata collection
		$collection = [
			['mailbox_id' => 1, 'uid' => 1, 'id' => 1],
			['mailbox_id' => 1, 'uid' => 2, 'id' => 2],
			['mailbox_id' => 2, 'uid' => 3, 'id' => 3],
		];
		//construct expected result
		$expected = [
			1 => [
				['id' => 1, 'uid' => 1],
				['id' => 2, 'uid' => 2],
			],
			2 => [
				['id' => 3, 'uid' => 3],
			],
		];
		// test service
		$result = self::invokePrivate($this->service, 'groupByMailbox', [$collection]);
		$this->assertEquals($expected, $result);
	}

	public function testGroupByAccount() {
		// construct mailbox collection
		$mailbox1 = new Mailbox();
		$mailbox1->setAccountId(1);
		$mailbox2 = new Mailbox();
		$mailbox2->setAccountId(1);
		$mailbox3 = new Mailbox();
		$mailbox3->setAccountId(2);
		$collection = [$mailbox1, $mailbox2, $mailbox3];
		// construct expected result
		$expected = [
			1 => [$mailbox1, $mailbox2],
			2 => [$mailbox3],
		];
		// test service
		$result = self::invokePrivate($this->service, 'groupByAccount', [$collection]);
		$this->assertEquals($expected, $result);
	}

	public function testChangeFlagsSingleMailbox() {
		$userId = 'user1';
		$flag = 'seen';
		// construct message data
		$messageIds = [100, 200, 300];
		$messageDetails = [
			['mailbox_id' => 1, 'uid' => 100, 'id' => 100],
			['mailbox_id' => 1, 'uid' => 200, 'id' => 200],
			['mailbox_id' => 1, 'uid' => 300, 'id' => 300],
		];
		// construct mailbox collection
		$mailbox1 = (new Mailbox())->fromRow([
			'id' => 1,
			'account_id' => 1,
			'name' => 'INBOX',
		]);
		$mailboxes = [$mailbox1];
		// construct account collection
		$account1 = (new MailAccount)->fromRow([
			'id' => 1,
			'user_id' => 'user1',
			'inbound_host' => 'imap.example.com',
			'inbound_user' => 'user1',
			'inbound_password' => 'password',
			'inbound_port' => 143,
			'inbound_ssl_mode' => 'none',
		]);
		$accounts = [$account1];

		$this->messageMapper->expects($this->once())
			->method('findMailboxAndUid')
			->with($messageIds)
			->willReturn($messageDetails);

		$this->mailboxMapper->expects($this->once())
			->method('findByIds')
			->with([1])
			->willReturn($mailboxes);

		$this->accountMapper->expects($this->once())
			->method('findByIds')
			->with($userId, [1])
			->willReturn($accounts);
		
		$client1 = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->clientFactory->expects($this->once())
			->method('getClient')
			->with(new Account($account1))
			->willReturn($client1);
		
		$this->mailManager->expects($this->once())
			->method('filterFlags')
			->willReturn(["\\$flag"]);

		$this->imapMessageMapper->expects($this->once())
			->method('setFlags')
			->with($client1, $mailbox1, array_column($messageDetails, 'uid'), ["\\$flag"], [])
			->willReturn([1 => true, 2 => true, 3 => true]);

		// test service
		$result = $this->service->changeFlags($userId, $messageIds, [$flag => true]);
		$this->assertEquals([100 => true, 200 => true, 300 => true], $result);
	}

	public function testChangeFlagsMultipleMailboxes() {
		$userId = 'user1';
		$flag = 'seen';
		// construct message data
		$messageIds = [100, 200, 300];
		$messageDetails = [
			['mailbox_id' => 1, 'uid' => 100, 'id' => 100],
			['mailbox_id' => 2, 'uid' => 200, 'id' => 200],
			['mailbox_id' => 2, 'uid' => 300, 'id' => 300],
		];
		// construct mailbox collection
		$mailbox1 = (new Mailbox())->fromRow([
			'id' => 1,
			'account_id' => 1,
			'name' => 'INBOX',
		]);
		$mailbox2 = (new Mailbox())->fromRow([
			'id' => 2,
			'account_id' => 1,
			'name' => 'Sent',
		]);
		$mailboxes = [$mailbox1, $mailbox2];
		// construct account collection
		$account1 = (new MailAccount)->fromRow([
			'id' => 1,
			'user_id' => 'user1',
			'inbound_host' => 'imap.example.com',
			'inbound_user' => 'user1',
			'inbound_password' => 'password',
			'inbound_port' => 143,
			'inbound_ssl_mode' => 'none',
		]);
		$accounts = [$account1];

		$this->messageMapper->expects($this->once())
			->method('findMailboxAndUid')
			->with($messageIds)
			->willReturn($messageDetails);

		$this->mailboxMapper->expects($this->once())
			->method('findByIds')
			->with([1, 2])
			->willReturn($mailboxes);

		$this->accountMapper->expects($this->once())
			->method('findByIds')
			->with($userId, [1])
			->willReturn($accounts);
		
		$client1 = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->clientFactory->expects($this->once())
			->method('getClient')
			->with(new Account($account1))
			->willReturn($client1);
		
		$this->mailManager->expects($this->exactly(2))
			->method('filterFlags')
			->willReturn(["\\$flag"]);

		$this->imapMessageMapper->expects($this->exactly(2))
			->method('setFlags')
			->withConsecutive(
				[$client1, $mailbox1, [100], ["\\$flag"], []],
				[$client1, $mailbox2, [200, 300], ["\\$flag"], []]
			)
			->willReturnOnConsecutiveCalls(
				[100 => true],
				[200 => true, 300 => true]
			);
		// test service
		$result = $this->service->changeFlags($userId, $messageIds, [$flag => true]);
		$this->assertEquals([100 => true, 200 => true, 300 => true], $result);
	}

}
