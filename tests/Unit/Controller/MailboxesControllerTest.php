<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Controller\MailboxesController;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Exception\NotImplemented;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\MailboxStats;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\DelegationService;
use OCA\Mail\Service\Sync\SyncService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;

class MailboxesControllerTest extends TestCase {
	/** @var string */
	private $appName = 'mail';

	/** @var IRequest|MockObject */
	private $request;

	/** @var AccountService|MockObject */
	private $accountService;

	/** @var string */
	private $userId = 'john';

	/** @var IMailManager|MockObject */
	private $mailManager;

	/** @var MailboxesController */
	private $controller;

	/** @var SyncService|MockObject */
	private $syncService;

	private IConfig|MockObject $config;
	private ITimeFactory|MockObject $timeFactory;
	private DelegationService|MockObject $delegationService;
	private IMailSearch|MockObject $mailSearch;

	public function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->mailManager = $this->createMock(IMailManager::class);
		$this->syncService = $this->createMock(SyncService::class);
		$this->config = $this->createMock(IConfig::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->delegationService = $this->createMock(DelegationService::class);
		$this->delegationService->method('resolveAccountUserId')->willReturn($this->userId);
		$this->delegationService->method('resolveMailboxUserId')->willReturn($this->userId);
		$this->mailSearch = $this->createMock(IMailSearch::class);

		$this->controller = new MailboxesController(
			$this->appName,
			$this->request,
			$this->accountService,
			$this->userId,
			$this->mailManager,
			$this->syncService,
			$this->config,
			$this->timeFactory,
			$this->delegationService,
			$this->mailSearch,
		);
	}

	public function testIndex() {
		$account = $this->createMock(Account::class);
		$folder = $this->createMock(Folder::class);
		$accountId = 28;
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->willReturn($account);
		$this->mailManager->expects($this->once())
			->method('getMailboxes')
			->with($this->equalTo($account))
			->willReturn([
				$folder
			]);
		$account->expects($this->once())
			->method('getEmail')
			->willReturn('user@example.com');
		$folder->expects($this->once())
			->method('getDelimiter')
			->willReturn('.');

		$result = $this->controller->index($accountId);

		$expected = new JSONResponse([
			'id' => 28,
			'email' => 'user@example.com',
			'mailboxes' => [
				$folder,
			],
			'delimiter' => '.',
		]);
		$this->assertEquals($expected, $result);
	}

	public function testShow() {
		$this->expectException(NotImplemented::class);

		$this->controller->show();
	}

	public function testCreate() {
		$account = $this->createStub(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setId(99);
		$accountId = 28;
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->equalTo($this->userId), $this->equalTo($accountId))
			->willReturn($account);
		$this->mailManager->expects($this->once())
			->method('createMailbox')
			->with($this->equalTo($account), $this->equalTo('new'))
			->willReturn($mailbox);
		$this->delegationService->expects($this->once())
			->method('logDelegatedAction')
			->with($this->userId, $this->userId, "$this->userId created mailbox: {$mailbox->getId()} on behalf of $this->userId");

		$response = $this->controller->create($accountId, 'new');

		$expected = new JSONResponse($mailbox);
		$this->assertEquals($expected, $response);
	}

	public function testPatchRenameLogsDelegatedAction(): void {
		$mailboxId = 13;
		$mailbox = new Mailbox();
		$mailbox->setId($mailboxId);
		$mailbox->setAccountId(28);
		$account = $this->createStub(Account::class);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->with($this->userId, $mailboxId)
			->willReturn($mailbox);
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, 28)
			->willReturn($account);
		$this->mailManager->expects($this->once())
			->method('renameMailbox')
			->with($account, $mailbox, 'renamed')
			->willReturn($mailbox);
		$this->delegationService->expects($this->once())
			->method('logDelegatedAction')
			->with($this->userId, $this->userId, "$this->userId changed mailbox: {$mailboxId}'s name to renamed on behalf of $this->userId");

		$response = $this->controller->patch($mailboxId, 'renamed');

		$this->assertEquals(new JSONResponse($mailbox), $response);
	}

	public function testPatchSubscribeLogsDelegatedAction(): void {
		$mailboxId = 13;
		$mailbox = new Mailbox();
		$mailbox->setId($mailboxId);
		$mailbox->setAccountId(28);
		$account = $this->createStub(Account::class);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->willReturn($mailbox);
		$this->accountService->expects($this->once())
			->method('find')
			->willReturn($account);
		$this->mailManager->expects($this->once())
			->method('updateSubscription')
			->with($account, $mailbox, true)
			->willReturn($mailbox);
		$this->delegationService->expects($this->once())
			->method('logDelegatedAction')
			->with($this->userId, $this->userId, "$this->userId subscribed to mailbox: $mailboxId on behalf of $this->userId");

		$this->controller->patch($mailboxId, null, true);
	}

	public function testPatchUnsubscribeLogsDelegatedAction(): void {
		$mailboxId = 13;
		$mailbox = new Mailbox();
		$mailbox->setId($mailboxId);
		$mailbox->setAccountId(28);
		$account = $this->createStub(Account::class);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->willReturn($mailbox);
		$this->accountService->expects($this->once())
			->method('find')
			->willReturn($account);
		$this->mailManager->expects($this->once())
			->method('updateSubscription')
			->with($account, $mailbox, false)
			->willReturn($mailbox);
		$this->delegationService->expects($this->once())
			->method('logDelegatedAction')
			->with($this->userId, $this->userId, "$this->userId unsubscribed to mailbox: $mailboxId on behalf of $this->userId");

		$this->controller->patch($mailboxId, null, false);
	}

	public function testPatchEnableSyncLogsDelegatedAction(): void {
		$mailboxId = 13;
		$mailbox = new Mailbox();
		$mailbox->setId($mailboxId);
		$mailbox->setAccountId(28);
		$account = $this->createStub(Account::class);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->willReturn($mailbox);
		$this->accountService->expects($this->once())
			->method('find')
			->willReturn($account);
		$this->mailManager->expects($this->once())
			->method('enableMailboxBackgroundSync')
			->with($mailbox, true)
			->willReturn($mailbox);
		$this->delegationService->expects($this->once())
			->method('logDelegatedAction')
			->with($this->userId, $this->userId, "$this->userId enabled background sync for mailbox: $mailboxId on behalf of $this->userId");

		$this->controller->patch($mailboxId, null, null, true);
	}

	public function testMarkAllAsReadLogsDelegatedAction(): void {
		$mailboxId = 13;
		$mailbox = new Mailbox();
		$mailbox->setId($mailboxId);
		$mailbox->setAccountId(28);
		$account = $this->createStub(Account::class);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->willReturn($mailbox);
		$this->accountService->expects($this->once())
			->method('find')
			->willReturn($account);
		$this->mailManager->expects($this->once())
			->method('markFolderAsRead')
			->with($account, $mailbox);
		$this->delegationService->expects($this->once())
			->method('logDelegatedAction')
			->with($this->userId, $this->userId, "$this->userId marked all messages as read in mailbox: $mailboxId on behalf of $this->userId");

		$this->controller->markAllAsRead($mailboxId);
	}

	public function testSetFlags(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(13);
		$mailbox->setAccountId(28);
		$account = $this->createStub(Account::class);
		$this->mailManager->method('getMailbox')
			->with($this->userId, 13)
			->willReturn($mailbox);
		$this->accountService->method('find')
			->with($this->userId, 28)
			->willReturn($account);
		$this->mailSearch->expects($this->once())
			->method('findMessageUids')
			->with($account, $mailbox, 'is:unread')
			->willReturn([101, 102]);
		$this->mailManager->expects($this->exactly(2))
			->method('flagMessages')
			->willReturnCallback(function (Account $a, Mailbox $mb, array $uids, string $flag, bool $value): void {
				$this->assertSame([101, 102], $uids);
				match ($flag) {
					'seen' => $this->assertTrue($value),
					'flagged' => $this->assertFalse($value),
				};
			});
		$this->delegationService->expects($this->once())
			->method('logDelegatedAction')
			->with($this->userId, $this->userId, "$this->userId updated flags on 2 messages in mailbox: 13 with [seen=true, flagged=false] on behalf of $this->userId");

		$response = $this->controller->setFlags(13, ['seen' => true, 'flagged' => 'false'], 'is:unread');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
	}

	public function testSetFlagsRejectsUnsupportedFlags(): void {
		$this->mailSearch->expects($this->never())
			->method('findMessageUids');
		$this->mailManager->expects($this->never())
			->method('flagMessages');

		$response = $this->controller->setFlags(13, ['$junk' => true]);

		$this->assertSame(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());
	}

	public function testSetFlagsRejectsEmptyFlags(): void {
		$this->mailManager->expects($this->never())
			->method('flagMessages');

		$response = $this->controller->setFlags(13, []);

		$this->assertSame(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());
	}

	public function testSetFlagsWithoutUser(): void {
		$controller = new MailboxesController(
			$this->appName,
			$this->request,
			$this->accountService,
			null,
			$this->mailManager,
			$this->syncService,
			$this->config,
			$this->timeFactory,
			$this->delegationService,
			$this->mailSearch,
		);
		$this->mailManager->expects($this->never())
			->method('flagMessages');

		$response = $controller->setFlags(13, ['seen' => true]);

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
	}

	public function testDestroyLogsDelegatedAction(): void {
		$mailboxId = 13;
		$mailbox = new Mailbox();
		$mailbox->setId($mailboxId);
		$mailbox->setAccountId(28);
		$account = $this->createStub(Account::class);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->willReturn($mailbox);
		$this->accountService->expects($this->once())
			->method('find')
			->willReturn($account);
		$this->mailManager->expects($this->once())
			->method('deleteMailbox')
			->with($account, $mailbox);
		$this->delegationService->expects($this->once())
			->method('logDelegatedAction')
			->with($this->userId, $this->userId, "$this->userId deleted mailbox: $mailboxId on behalf of $this->userId");

		$this->controller->destroy($mailboxId);
	}

	public function testClearMailboxLogsDelegatedAction(): void {
		$mailboxId = 13;
		$mailbox = new Mailbox();
		$mailbox->setId($mailboxId);
		$mailbox->setAccountId(28);
		$account = $this->createStub(Account::class);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->willReturn($mailbox);
		$this->accountService->expects($this->once())
			->method('find')
			->willReturn($account);
		$this->mailManager->expects($this->once())
			->method('clearMailbox')
			->with($account, $mailbox);
		$this->delegationService->expects($this->once())
			->method('logDelegatedAction')
			->with($this->userId, $this->userId, "$this->userId cleared mailbox: $mailboxId on behalf of $this->userId");

		$this->controller->clearMailbox($mailboxId);
	}

	public function testRepairLogsDelegatedAction(): void {
		$mailboxId = 13;
		$mailbox = new Mailbox();
		$mailbox->setId($mailboxId);
		$mailbox->setAccountId(28);
		$account = $this->createStub(Account::class);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->willReturn($mailbox);
		$this->accountService->expects($this->once())
			->method('find')
			->willReturn($account);
		$this->syncService->expects($this->once())
			->method('repairSync')
			->with($account, $mailbox);
		$this->delegationService->expects($this->once())
			->method('logDelegatedAction')
			->with($this->userId, $this->userId, "$this->userId repaired mailbox: $mailboxId on behalf of $this->userId");

		$this->controller->repair($mailboxId);
	}

	public function testStats(): void {
		$mailbox = new Mailbox();
		$mailbox->setUnseen(10);
		$mailbox->setMessages(42);
		$mailbox->setMyAcls(null);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->with('john', 13)
			->willReturn($mailbox);

		$response = $this->controller->stats(13);

		$stats = new MailboxStats(42, 10, null);
		$expected = new JSONResponse($stats);
		$this->assertEquals($expected, $response);
	}

	public function testUpdate() {
		$this->expectException(NotImplemented::class);

		$this->controller->update();
	}
}
