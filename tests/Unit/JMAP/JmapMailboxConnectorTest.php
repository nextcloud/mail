<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\JMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\JMAP\JmapMailboxConnector;
use OCA\Mail\Service\JMAP\JmapOperationsService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class JmapMailboxConnectorTest extends TestCase {
	private JmapOperationsService&MockObject $jmapOperationsService;
	private MailboxMapper&MockObject $mailboxMapper;
	private JmapMailboxConnector $connector;
	private Account&MockObject $account;

	protected function setUp(): void {
		parent::setUp();

		$this->jmapOperationsService = $this->createMock(JmapOperationsService::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->account = $this->createMock(Account::class);
		$this->account->method('getId')->willReturn(42);

		$this->connector = new JmapMailboxConnector(
			$this->jmapOperationsService,
			$this->mailboxMapper,
			$this->createMock(ITimeFactory::class),
			$this->createMock(IEventDispatcher::class),
			$this->createMock(LoggerInterface::class),
		);
	}

	public function testCreateFindsParentByNameHashAndHashesFullPath(): void {
		$parent = new Mailbox();
		$parent->setRemoteId('parent-id');

		$this->jmapOperationsService->expects(self::once())
			->method('connect')
			->with($this->account)
			->willReturn(true);
		$this->mailboxMapper->expects(self::once())
			->method('find')
			->with($this->account, 'Parent')
			->willReturn($parent);
		$this->jmapOperationsService->expects(self::once())
			->method('collectionCreate')
			->with($parent, self::callback(static function (Mailbox $mailbox): bool {
				return $mailbox->getName() === 'Child';
			}))
			->willReturnCallback(static function (?Mailbox $location, Mailbox $mailbox): Mailbox {
				self::assertSame('parent-id', $location?->getRemoteId());
				$mailbox->setRemoteId('child-id');
				$mailbox->setNameHash(md5('child-id'));
				return $mailbox;
			});
		$this->mailboxMapper->expects(self::once())
			->method('insert')
			->with(self::callback(static function (Mailbox $mailbox): bool {
				return $mailbox->getName() === 'Parent/Child'
					&& $mailbox->getNameHash() === md5('Parent/Child');
			}))
			->willReturnArgument(0);

		$mailbox = $this->connector->create($this->account, 'Parent/Child');

		self::assertSame('Parent/Child', $mailbox->getName());
		self::assertSame(md5('Parent/Child'), $mailbox->getNameHash());
	}

	public function testRenameHashesFullPath(): void {
		$mailbox = new Mailbox();
		$mailbox->setRemoteId('mailbox-id');
		$mailbox->setName('Parent/Before');
		$mailbox->setNameHash(md5('Parent/Before'));

		$this->jmapOperationsService->expects(self::once())
			->method('connect')
			->with($this->account)
			->willReturn(true);
		$this->jmapOperationsService->expects(self::once())
			->method('collectionModify')
			->with('mailbox-id', self::callback(static function (Mailbox $mailbox): bool {
				return $mailbox->getName() === 'After';
			}), ['name'])
			->willReturnArgument(1);
		$this->mailboxMapper->expects(self::once())
			->method('update')
			->with(self::callback(static function (Mailbox $mailbox): bool {
				return $mailbox->getName() === 'Parent/After'
					&& $mailbox->getNameHash() === md5('Parent/After');
			}))
			->willReturnArgument(0);

		$renamed = $this->connector->rename($this->account, $mailbox, 'Parent/After');

		self::assertSame('Parent/After', $renamed->getName());
		self::assertSame(md5('Parent/After'), $renamed->getNameHash());
	}
}
