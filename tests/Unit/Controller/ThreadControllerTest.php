<?php

declare(strict_types=1);

/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Controller\ThreadController;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;

class ThreadControllerTest extends TestCase {
	/** @var string */
	private $appName;

	/** @var IRequest|MockObject */
	private $request;

	/** @var string */
	private $userId;

	/** @var AccountService|MockObject */
	private $accountService;

	/** @var IMailManager|MockObject */
	private $mailManager;

	/** @var ThreadController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->appName = 'mail';
		$this->request = $this->getMockBuilder(IRequest::class)->getMock();
		$this->userId = 'john';
		$this->accountService = $this->createMock(AccountService::class);
		$this->mailManager = $this->createMock(IMailManager::class);

		$this->controller = new ThreadController(
			$this->appName,
			$this->request,
			$this->userId,
			$this->accountService,
			$this->mailManager
		);
	}

	public function testMoveForbidden() {
		$this->mailManager
			->method('getMessage')
			->willThrowException(new DoesNotExistException('for some reason there is no such record'));

		$response = $this->controller->move(100, 20);

		$this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
	}

	public function testMoveInbox(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setTrashMailboxId(80);
		$this->accountService
			->method('find')
			->willReturn(new Account($mailAccount));
		$srcMailbox = new Mailbox();
		$srcMailbox->setId(20);
		$srcMailbox->setName('INBOX');
		$srcMailbox->setAccountId($mailAccount->getId());
		$dstMailbox = new Mailbox();
		$dstMailbox->setId(40);
		$dstMailbox->setName('Archive');
		$dstMailbox->setAccountId($mailAccount->getId());
		$this->mailManager
			->method('getMailbox')
			->willReturnMap([
				[$this->userId, $srcMailbox->getId(), $srcMailbox],
				[$this->userId, $dstMailbox->getId(), $dstMailbox],
			]);
		$message = new Message();
		$message->setId(300);
		$message->setMailboxId($srcMailbox->getId());
		$message->setThreadRootId('some-thread-root-id-1');
		$this->mailManager
			->method('getMessage')
			->willReturn($message);
		$this->mailManager
			->expects(self::once())
			->method('moveThread');

		$response = $this->controller->move($message->getId(), $dstMailbox->getId());

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
	}

	public function testMoveTrash(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setTrashMailboxId(80);
		$this->accountService
			->method('find')
			->willReturn(new Account($mailAccount));
		$srcMailbox = new Mailbox();
		$srcMailbox->setId(80);
		$srcMailbox->setName('Trash');
		$srcMailbox->setAccountId($mailAccount->getId());
		$dstMailbox = new Mailbox();
		$dstMailbox->setId(20);
		$dstMailbox->setName('Archive');
		$dstMailbox->setAccountId($mailAccount->getId());
		$this->mailManager
			->method('getMailbox')
			->willReturnMap([
				[$this->userId, $srcMailbox->getId(), $srcMailbox],
				[$this->userId, $dstMailbox->getId(), $dstMailbox],
			]);
		$message = new Message();
		$message->setId(300);
		$message->setMailboxId($srcMailbox->getId());
		$message->setThreadRootId('some-thread-root-id-1');
		$this->mailManager
			->method('getMessage')
			->willReturn($message);
		$this->mailManager
			->expects(self::once())
			->method('moveThread');

		$response = $this->controller->move($message->getId(), $dstMailbox->getId());

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
	}

	public function testDeleteForbidden(): void {
		$this->mailManager
			->method('getMessage')
			->willThrowException(new DoesNotExistException('for some reason there is no such record'));

		$response = $this->controller->delete(100);

		$this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
	}

	public function testDeleteInbox(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setTrashMailboxId(80);
		$this->accountService
			->method('find')
			->willReturn(new Account($mailAccount));
		$mailbox = new Mailbox();
		$mailbox->setId(20);
		$mailbox->setAccountId($mailAccount->getId());
		$this->mailManager
			->method('getMailbox')
			->willReturn($mailbox);
		$message = new Message();
		$message->setId(300);
		$message->setMailboxId($mailbox->getId());
		$message->setThreadRootId('some-thread-root-id-1');
		$this->mailManager
			->method('getMessage')
			->willReturn($message);
		$this->mailManager
			->expects(self::once())
			->method('deleteThread');

		$response = $this->controller->delete(300);

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
	}

	public function testDeleteTrash(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setTrashMailboxId(80);
		$this->accountService
			->method('find')
			->willReturn(new Account($mailAccount));
		$mailbox = new Mailbox();
		$mailbox->setId(80);
		$mailbox->setAccountId($mailAccount->getId());
		$this->mailManager
			->method('getMailbox')
			->willReturn($mailbox);
		$message = new Message();
		$message->setId(300);
		$message->setMailboxId($mailbox->getId());
		$message->setThreadRootId('some-thread-root-id-1');
		$this->mailManager
			->method('getMessage')
			->willReturn($message);
		$this->mailManager
			->expects(self::once())
			->method('deleteThread');

		$response = $this->controller->delete($message->getId());

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
	}
}
