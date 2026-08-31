<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\Tag;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\ImapMessageConnector;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\Sync\ImapToDbSynchronizer;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Every connector method that opens an IMAP client via ProtocolFactory::imapClient()
 * must log it out again even when the underlying operation fails, otherwise the
 * connection to the mail server is leaked (see moveMessages/deleteMessages/flagMessages/
 * tagMessages/isPermflagsEnabled).
 */
class ImapMessageConnectorTest extends TestCase {
	private ProtocolFactory&MockObject $protocolFactory;
	private ImapToDbSynchronizer&MockObject $synchronizer;
	private FolderMapper&MockObject $imapMailboxMapper;
	private MessageMapper&MockObject $imapMessageMapper;
	private LoggerInterface&MockObject $logger;
	private ImapMessageConnector $connector;
	private Account&MockObject $account;
	private Horde_Imap_Client_Socket&MockObject $client;

	protected function setUp(): void {
		parent::setUp();

		$this->protocolFactory = $this->createMock(ProtocolFactory::class);
		$this->synchronizer = $this->createMock(ImapToDbSynchronizer::class);
		$this->imapMailboxMapper = $this->createMock(FolderMapper::class);
		$this->imapMessageMapper = $this->createMock(MessageMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->connector = new ImapMessageConnector(
			$this->protocolFactory,
			$this->synchronizer,
			$this->imapMailboxMapper,
			$this->imapMessageMapper,
			$this->logger,
		);

		$this->account = $this->createMock(Account::class);
		$this->client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->protocolFactory->method('imapClient')
			->with($this->account)
			->willReturn($this->client);
	}

	public function testMoveMessagesLogsOutClientWhenMapperThrows(): void {
		$sourceMailbox = new Mailbox();
		$sourceMailbox->setName('INBOX');
		$targetMailbox = new Mailbox();
		$targetMailbox->setName('Archive');
		$message = new Message();
		$message->setUid(1);

		$this->imapMessageMapper->method('move')
			->willThrowException(new ServiceException('could not move'));
		$this->client->expects(self::once())
			->method('logout');

		$this->expectException(ServiceException::class);

		$this->connector->moveMessages($this->account, $targetMailbox, $sourceMailbox, $message);
	}

	public function testDeleteMessagesLogsOutClientWhenMapperThrows(): void {
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$message = new Message();
		$message->setUid(1);

		$this->imapMessageMapper->method('expunge')
			->willThrowException(new ServiceException('could not expunge'));
		$this->client->expects(self::once())
			->method('logout');

		$this->expectException(ServiceException::class);

		$this->connector->deleteMessages($this->account, $mailbox, $message);
	}

	public function testFlagMessagesLogsOutClientWhenMapperThrows(): void {
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$message = new Message();
		$message->setUid(1);

		$this->imapMessageMapper->method('addFlag')
			->willThrowException(new Horde_Imap_Client_Exception('store failed'));
		$this->client->expects(self::once())
			->method('logout');

		$this->expectException(ServiceException::class);

		$this->connector->flagMessages($this->account, $mailbox, 'seen', true, $message);
	}

	public function testTagMessagesLogsOutClientWhenPermflagsCheckThrows(): void {
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$tag = new Tag();
		$tag->setDisplayName('Important');
		$tag->setImapLabel('$important');
		$message = new Message();
		$message->setUid(1);

		$this->client->method('status')
			->willThrowException(new Horde_Imap_Client_Exception('status failed'));
		$this->client->expects(self::once())
			->method('logout');

		$this->expectException(ServiceException::class);

		$this->connector->tagMessages($this->account, $mailbox, $tag, true, $message);
	}

	public function testTagMessagesLogsOutClientWhenPermflagsNotSupported(): void {
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$tag = new Tag();
		$tag->setDisplayName('Important');
		$tag->setImapLabel('$important');
		$message = new Message();
		$message->setUid(1);

		$this->client->method('status')
			->willReturn(['permflags' => []]);
		$this->client->expects(self::once())
			->method('logout');

		$result = $this->connector->tagMessages($this->account, $mailbox, $tag, true, $message);

		self::assertSame([], $result);
	}

	public function testIsPermflagsEnabledLogsOutClientWhenStatusThrows(): void {
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');

		$this->client->method('status')
			->willThrowException(new Horde_Imap_Client_Exception('status failed'));
		$this->client->expects(self::once())
			->method('logout');

		$this->expectException(ServiceException::class);

		$this->connector->isPermflagsEnabled($this->account, $mailbox);
	}

	public function testIsPermflagsEnabledLogsOutClientOnSuccess(): void {
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');

		$this->client->method('status')
			->willReturn(['permflags' => ['\\*']]);
		$this->client->expects(self::once())
			->method('logout');

		$result = $this->connector->isPermflagsEnabled($this->account, $mailbox);

		self::assertTrue($result);
	}
}
