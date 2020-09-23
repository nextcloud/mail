<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Tests\Unit\Listener;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Events\BeforeMessageDeletedEvent;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\Listener\TrashMailboxCreatorListener;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class TrashMailboxCreatorListenerTest extends TestCase {

	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

	/** @var IMAPClientFactory|MockObject */
	private $imapClientFactory;

	/** @var MailboxSync|MockObject */
	private $mailboxSync;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var TrashMailboxCreatorListener */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->mailboxSync = $this->createMock(MailboxSync::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->listener = new TrashMailboxCreatorListener(
			$this->mailboxMapper,
			$this->imapClientFactory,
			$this->mailboxSync,
			$this->logger
		);
	}

	public function testHandleUnrelated(): void {
		$event = new Event();

		$this->listener->handle($event);

		$this->addToAssertionCount(1);
	}

	public function testHandleDoesAlreadyExist(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$event = new BeforeMessageDeletedEvent(
			$account,
			'INBOX',
			123
		);
		$mailbox = new Mailbox();
		$this->mailboxMapper->expects($this->once())
			->method('findSpecial')
			->with($account, 'trash')
			->willReturn($mailbox);

		$this->listener->handle($event);
	}

	public function testHandleDoesNotExistCantCreate(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$event = new BeforeMessageDeletedEvent(
			$account,
			'INBOX',
			123
		);
		$this->mailboxMapper->expects($this->once())
			->method('findSpecial')
			->with($account, 'trash')
			->willThrowException(new DoesNotExistException(''));
		$client = $this->createMock(\Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->with($account)
			->willReturn($client);
		$client->expects($this->once())
			->method('createMailbox')
			->with(
				'Trash',
				[
					'special_use' => [
						\Horde_Imap_Client::SPECIALUSE_TRASH,
					],
				]
			)
			->willThrowException(new \Horde_Imap_Client_Exception());
		$this->logger->expects($this->once())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandleDoesNotExist(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$event = new BeforeMessageDeletedEvent(
			$account,
			'INBOX',
			123
		);
		$this->mailboxMapper->expects($this->once())
			->method('findSpecial')
			->with($account, 'trash')
			->willThrowException(new DoesNotExistException(''));
		$client = $this->createMock(\Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->with($account)
			->willReturn($client);
		$client->expects($this->once())
			->method('createMailbox')
			->with(
				'Trash',
				[
					'special_use' => [
						\Horde_Imap_Client::SPECIALUSE_TRASH,
					],
				]
			);
		$this->mailboxSync->expects($this->once())
			->method('sync')
			->with($account, $this->logger, true);
		$this->logger->expects($this->never())
			->method('error');

		$this->listener->handle($event);
	}
}
