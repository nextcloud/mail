<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Controller\ThreadController;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Model\EventData;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCA\Mail\Service\SnoozeService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

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

	/** @var SnoozeService|MockObject */
	private $snoozeService;

	/** @var AiIntegrationsService|MockObject */
	private $aiIntergrationsService;

	/** @var ThreadController */
	private $controller;

	/** @var LoggerInterface|MockObject */
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->appName = 'mail';
		$this->request = $this->getMockBuilder(IRequest::class)->getMock();
		$this->userId = 'john';
		$this->accountService = $this->createMock(AccountService::class);
		$this->mailManager = $this->createMock(IMailManager::class);
		$this->snoozeService = $this->createMock(SnoozeService::class);
		$this->aiIntergrationsService = $this->createMock(AiIntegrationsService::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->controller = new ThreadController(
			$this->appName,
			$this->request,
			$this->userId,
			$this->accountService,
			$this->mailManager,
			$this->snoozeService,
			$this->aiIntergrationsService,
			$this->logger,
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

	public function testSummarizeThread(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$this->accountService
			->expects(self::once())
			->method('find')
			->with('john', 1)
			->willReturn(new Account($mailAccount));
		$mailbox = new Mailbox();
		$mailbox->setId(20);
		$mailbox->setAccountId($mailAccount->getId());
		$this->mailManager
			->method('getMailbox')
			->willReturn($mailbox);
		$message1 = new Message();
		$message1->setId(300);
		$message1->setMailboxId($mailbox->getId());
		$message1->setPreviewText('message1');
		$message1->setThreadRootId('some-thread-root-id-1');

		$message2 = new Message();
		$message2->setId(301);
		$message2->setMailboxId($mailbox->getId());
		$message2->setThreadRootId('some-thread-root-id-1');

		$message3 = new Message();
		$message3->setId(302);
		$message3->setMailboxId($mailbox->getId());
		$message3->setThreadRootId('some-thread-root-id-1');

		$this->mailManager
			->method('getMessage')
			->willReturn($message1);
		$this->aiIntergrationsService
			->expects(self::once())
			->method('summarizeThread')
			->willReturn('example summary');


		$response = $this->controller->summarize(300);
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals(['data' => 'example summary'], $response->getData());

	}

	public function testGenerateEventData(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$this->accountService->expects(self::once())
			->method('find')
			->with('john', 1)
			->willReturn(new Account($mailAccount));
		$mailbox = new Mailbox();
		$mailbox->setId(20);
		$mailbox->setAccountId($mailAccount->getId());
		$this->mailManager->expects(self::once())
			->method('getMailbox')
			->willReturn($mailbox);
		$message1 = new Message();
		$message1->setId(300);
		$message1->setMailboxId($mailbox->getId());
		$message1->setPreviewText('message1');
		$message1->setThreadRootId('some-thread-root-id-1');
		$message2 = new Message();
		$message2->setId(301);
		$message2->setMailboxId($mailbox->getId());
		$message2->setThreadRootId('some-thread-root-id-1');
		$message3 = new Message();
		$message3->setId(302);
		$message3->setMailboxId($mailbox->getId());
		$message3->setThreadRootId('some-thread-root-id-1');
		$this->mailManager->expects(self::once())
			->method('getMessage')
			->willReturn($message1);
		$this->aiIntergrationsService
			->expects(self::once())
			->method('generateEventData')
			->willReturn(new EventData('S', 'D'));

		$response = $this->controller->generateEventData(300);

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
	}

}
