<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Controller\MailboxesApiController;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message as DbMessage;
use OCA\Mail\Folder;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;

class MailboxesApiControllerTest extends TestCase {
	private const USER_ID = 'user';

	private MailboxesApiController $controller;

	private IRequest&MockObject $request;
	private IMailManager|MockObject $mailManager;
	private AccountService&MockObject $accountService;
	private MockObject|IMailSearch $mailSearch;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->mailManager = $this->createMock(IMailManager::class);
		$this->mailSearch = $this->createMock(IMailSearch::class);

		$this->controller = new MailboxesApiController(
			'mail',
			$this->request,
			self::USER_ID,
			$this->mailManager,
			$this->accountService,
			$this->mailSearch,

		);
	}

	public function testListMailboxesWithoutUser() {
		$controller = new MailboxesApiController(
			'mail',
			$this->request,
			null,
			$this->mailManager,
			$this->accountService,
			$this->mailSearch,
		);

		$this->accountService->expects(self::never())
			->method('find');

		$accountId = 28;

		$actual = $controller->list($accountId);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $actual->getStatus());
	}


	public function testListMailboxes() {
		$account = $this->createMock(Account::class);
		$folder = $this->createMock(Folder::class);
		$accountId = 42;
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo(self::USER_ID), $this->equalTo($accountId))
			->willReturn($account);
		$this->mailManager->expects($this->once())
			->method('getMailboxes')
			->with($this->equalTo($account))
			->willReturn([
				$folder
			]);
		$actual = $this->controller->list($accountId);

		$this->assertEquals(Http::STATUS_OK, $actual->getStatus());
		$this->assertEquals([$folder], $actual->getData());
	}

	public function testListMessagesWithoutUser() {
		$controller = new MailboxesApiController(
			'mail',
			$this->request,
			null,
			$this->mailManager,
			$this->accountService,
			$this->mailSearch,
		);

		$this->accountService->expects(self::never())
			->method('find');

		$accountId = 28;

		$actual = $controller->listMessages($accountId);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $actual->getStatus());
	}


	public function testListMessages(): void {
		$accountId = 100;
		$mailboxId = 101;
		$mailbox = new Mailbox();
		$mailbox->setAccountId($accountId);
		$this->mailManager->expects(self::once())
			->method('getMailbox')
			->with(SELF::USER_ID, $mailboxId)
			->willReturn($mailbox);
		$mailAccount = new MailAccount();
		$account = new Account($mailAccount);
		$this->accountService->expects(self::once())
			->method('find')
			->with(SELF::USER_ID, $accountId)
			->willReturn($account);

		$messages = [
			new DbMessage(),
			new DbMessage(),
		];
		$this->mailSearch->expects(self::once())
			->method('findMessages')
			->with(
				$account,
				$mailbox,
				'DESC',
				null,
				null,
				null,
				SELF::USER_ID,
				'threaded',
			)->willReturn($messages);

		$actual = $this->controller->listMessages($mailboxId);

		$this->assertEquals(Http::STATUS_OK, $actual->getStatus());
		$this->assertEquals($messages, $actual->getData());
	}

	public function testListMessagesInvalidMailbox(): void {
		$accountId = 100;
		$mailboxId = 101;
		$mailbox = new Mailbox();
		$mailbox->setAccountId($accountId);
		$this->mailManager->expects(self::once())
			->method('getMailbox')
			->with(SELF::USER_ID, $mailboxId)
			->willThrowException(new DoesNotExistException(''));
		$this->accountService->expects(self::never())
			->method('find');


		$actual = $this->controller->listMessages($mailboxId);

		$this->assertEquals(Http::STATUS_FORBIDDEN, $actual->getStatus());
	}



}
