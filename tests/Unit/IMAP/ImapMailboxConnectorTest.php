<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\ImapMailboxConnector;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\Protocol\ProtocolFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ImapMailboxConnectorTest extends TestCase {
	private ProtocolFactory&MockObject $protocolFactory;
	private MailboxSync&MockObject $mailboxSync;
	private FolderMapper&MockObject $folderMapper;
	private MailboxMapper&MockObject $mailboxMapper;
	private ImapMailboxConnector $connector;

	protected function setUp(): void {
		parent::setUp();

		$this->protocolFactory = $this->createMock(ProtocolFactory::class);
		$this->mailboxSync = $this->createMock(MailboxSync::class);
		$this->folderMapper = $this->createMock(FolderMapper::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);

		$this->connector = new ImapMailboxConnector(
			$this->protocolFactory,
			$this->mailboxSync,
			$this->folderMapper,
			$this->mailboxMapper,
			$this->createMock(LoggerInterface::class),
		);
	}

	public function testCreateMapsSpecialUseToImapAttribute(): void {
		$account = new Account(new MailAccount());
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = new Mailbox();

		$this->protocolFactory->method('imapClient')
			->willReturn($client);
		$this->folderMapper->expects(self::once())
			->method('createFolder')
			->with($client, 'Drafts', [Horde_Imap_Client::SPECIALUSE_DRAFTS])
			->willReturn($this->createMock(Folder::class));
		$this->mailboxMapper->expects(self::once())
			->method('find')
			->with($account, 'Drafts')
			->willReturn($mailbox);

		$result = $this->connector->create($account, 'Drafts', [Mailbox::SPECIAL_USE_DRAFTS]);

		$this->assertSame($mailbox, $result);
	}
}
