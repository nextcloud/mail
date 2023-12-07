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
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Events\DraftSavedEvent;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Listener\DeleteDraftListener;
use OCA\Mail\Model\NewMessageData;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class DeleteDraftListenerTest extends TestCase {
	/** @var IMAPClientFactory|MockObject */
	private $imapClientFactory;

	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var IEventDispatcher|MockObject */
	private $eventDispatcher;

	/** @var IEventListener */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->listener = new DeleteDraftListener(
			$this->imapClientFactory,
			$this->mailboxMapper,
			$this->messageMapper,
			$this->logger,
			$this->eventDispatcher
		);
	}

	public function testHandleUnrelated(): void {
		$event = new Event();

		$this->listener->handle($event);

		$this->addToAssertionCount(1);
	}

	public function testHandleDraftSavedEventNoUid(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		/** @var NewMessageData|MockObject $newMessageData */
		$newMessageData = $this->createMock(NewMessageData::class);
		$event = new DraftSavedEvent(
			$account,
			$newMessageData,
			null
		);
		$this->messageMapper->expects($this->never())
			->method('addFlag');
		$this->logger->expects($this->never())
			->method('error');
		$this->eventDispatcher->expects($this->never())
			->method('dispatchTyped');

		$this->listener->handle($event);
	}

	public function testHandleDraftSavedEventNoDraftMailboxSet(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$account->method('getMailAccount')->willReturn($mailAccount);
		/** @var NewMessageData|MockObject $newMessageData */
		$newMessageData = $this->createMock(NewMessageData::class);
		$draft = new Message();
		$uid = 123;
		$draft->setUid($uid);
		$event = new DraftSavedEvent(
			$account,
			$newMessageData,
			$draft
		);
		/** @var \Horde_Imap_Client_Socket|MockObject $client */
		$client = $this->createMock(\Horde_Imap_Client_Socket::class);
		$this->imapClientFactory
			->method('getClient')
			->with($account)
			->willReturn($client);
		$mailbox = new Mailbox();
		$mailbox->setName('Drafts');
		$this->mailboxMapper->expects($this->never())
			->method('findById');
		$this->logger->expects($this->once())->method('warning');

		$this->listener->handle($event);
	}

	public function testHandleDraftSavedEventDraftMailboxNotFound(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setDraftsMailboxId(123);
		$account->method('getMailAccount')->willReturn($mailAccount);
		/** @var NewMessageData|MockObject $newMessageData */
		$newMessageData = $this->createMock(NewMessageData::class);
		$draft = new Message();
		$uid = 123;
		$draft->setUid($uid);
		$event = new DraftSavedEvent(
			$account,
			$newMessageData,
			$draft
		);
		/** @var \Horde_Imap_Client_Socket|MockObject $client */
		$client = $this->createMock(\Horde_Imap_Client_Socket::class);
		$this->imapClientFactory
			->method('getClient')
			->with($account)
			->willReturn($client);
		$mailbox = new Mailbox();
		$mailbox->setName('Drafts');
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(123)
			->willThrowException(new DoesNotExistException(""));
		$this->logger->expects($this->once())->method('warning');

		$this->listener->handle($event);
	}

	public function testHandleMessageSentEventNoUid(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		/** @var NewMessageData|MockObject $newMessageData */
		$newMessageData = $this->createMock(NewMessageData::class);
		$event = new DraftSavedEvent(
			$account,
			$newMessageData,
			null
		);
		$this->messageMapper->expects($this->never())
			->method('addFlag');
		$this->logger->expects($this->never())
			->method('error');
		$this->eventDispatcher->expects($this->never())
			->method('dispatchTyped');

		$this->listener->handle($event);
	}
}
