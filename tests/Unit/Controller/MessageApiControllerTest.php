<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Controller\MessageApiController;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\UploadException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\OutboxService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\IRequest;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class MessageApiControllerTest extends TestCase {
	private string $appName;
	private string $userId;
	private AliasesService|MockObject $aliasesService;
	private AttachmentService|MockObject $attachmentService;
	private OutboxService|MockObject $outboxService;
	private LoggerInterface|MockObject $logger;
	private MockObject|ITimeFactory $time;
	private MessageApiController $controller;
	private MockObject|AccountService $accountService;
	private OutboxService|MockObject $service;
	private MockObject|IRequest $request;
	private string $fromEmail = 'john@test.com';
	private int $accountId = 1;
	private Account $account;
	private LocalMessage $message;

	protected function setUp(): void {
		parent::setUp();

		$this->appName = 'mail';
		$this->service = $this->createMock(OutboxService::class);
		$this->userId = 'john';
		$this->request = $this->createMock(IRequest::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->aliasesService = $this->createMock(AliasesService::class);
		$this->attachmentService = $this->createMock(AttachmentService::class);
		$this->outboxService = $this->createMock(OutboxService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->time = $this->createMock(ITimeFactory::class);

		$this->controller = new MessageApiController($this->appName,
			$this->userId,
			$this->request,
			$this->accountService,
			$this->aliasesService,
			$this->attachmentService,
			$this->outboxService,
			$this->logger,
			$this->time
		);

		$mailAccount = new MailAccount();
		$mailAccount->setId($this->accountId);
		$mailAccount->setEmail($this->fromEmail);
		$this->account = new Account($mailAccount);
		$this->message = new LocalMessage();
		$this->message->setAccountId($this->accountId);
		$this->message->setSubject('');
		$this->message->setBody('');
		$this->message->setHtml(true);
		$this->message->setType(LocalMessage::TYPE_OUTGOING);
	}

	/**
	 * @dataProvider mailData
	 */
	public function testSend($messageStatus, $expected): void {
		$this->message->setStatus($messageStatus);

		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $this->account->getId())
			->willReturn($this->account);
		$this->aliasesService->expects(self::never())
			->method('findByAliasAndUserId');
		$this->request->expects(self::once())
			->method('getUploadedFile')
			->willReturn(null);
		$this->attachmentService->expects(self::never())
			->method('addFile');
		$this->attachmentService->expects(self::never())
			->method('deleteAttachment');
		$this->outboxService->expects(self::once())
			->method('saveMessage');
		$this->outboxService->expects(self::once())
			->method('sendMessage')
			->willReturn($this->message);

		$actual = $this->controller->send(
			$this->accountId,
			$this->fromEmail,
			'',
			'',
			true,
			[]
		);

		$this->assertEquals($expected, $actual);
	}

	public function mailData() {
		return [
			[LocalMessage::STATUS_PROCESSED, new DataResponse('Success', Http::STATUS_OK)],
			[LocalMessage::STATUS_NO_SENT_MAILBOX, new DataResponse('Configuration error: Cannot send message without sent mailbox.', Http::STATUS_FORBIDDEN)],
			[LocalMessage::STATUS_SMPT_SEND_FAIL, new DataResponse('An SMTP error occured, please check your mail server logs.', Http::STATUS_INTERNAL_SERVER_ERROR)],
			[LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL, new DataResponse('Email was sent but could not be copied to sent mailbox.', Http::STATUS_ACCEPTED)],
			[LocalMessage::STATUS_TOO_MANY_RECIPIENTS, new DataResponse('An error occured, please check the logs.', Http::STATUS_INTERNAL_SERVER_ERROR)],
		];
	}

	/**
	 * @dataProvider exceptionData
	 */
	public function testSendException($exception, $expected): void {
		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $this->account->getId())
			->willReturn($this->account);
		$this->aliasesService->expects(self::never())
			->method('findByAliasAndUserId');
		$this->request->expects(self::once())
			->method('getUploadedFile')
			->willReturn(null);
		$this->attachmentService->expects(self::never())
			->method('addFile');
		$this->attachmentService->expects(self::never())
			->method('deleteAttachment');
		$this->outboxService->expects(self::once())
			->method('saveMessage');
		$this->outboxService->expects(self::once())
			->method('sendMessage')
			->willThrowException($exception);

		$actual = $this->controller->send(
			$this->accountId,
			$this->fromEmail,
			'',
			'',
			true,
			[]
		);

		$this->assertEquals($expected, $actual);
	}

	public function exceptionData() {
		return [
			[new ServiceException(), new DataResponse(new ServiceException(), Http::STATUS_BAD_REQUEST)],
			[new Exception(), new DataResponse(new Exception(), Http::STATUS_INTERNAL_SERVER_ERROR)],
		];
	}

	public function testWithAttachment(): void {
		$this->message->setStatus(LocalMessage::STATUS_PROCESSED);
		$attachments = [
			'name' => [
				0 => [
					'Test'
				]
			],
			'type' => [
				0 => [
					'Test'
				]
			],
			'size' => [
				0 => [
					10
				]
			],
			'tmp_name' => [
				0 => [
					'Test'
				]
			],
		];
		$localAttachment = new LocalAttachment();
		$localAttachment->setId(1);

		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $this->account->getId())
			->willReturn($this->account);
		$this->aliasesService->expects(self::never())
			->method('findByAliasAndUserId');
		$this->request->expects(self::once())
			->method('getUploadedFile')
			->willReturn($attachments);
		$this->attachmentService->expects(self::once())
			->method('addFile')
			->willReturn($localAttachment);
		$this->attachmentService->expects(self::never())
			->method('deleteAttachment');
		$this->outboxService->expects(self::once())
			->method('saveMessage');
		$this->outboxService->expects(self::once())
			->method('sendMessage')
			->willReturn($this->message);

		$expected = new DataResponse('Success', Http::STATUS_OK);
		$actual = $this->controller->send(
			$this->accountId,
			$this->fromEmail,
			'',
			'',
			true,
			[]
		);

		$this->assertEquals($expected, $actual);
	}

	public function testWithAttachmentError(): void {
		$attachments = [
			'name' => [
				0 => [
					'Test'
				],
			],
			'type' => [
				0 => [
					'Test'
				]
			],
			'size' => [
				0 => [
					10
				]
			],
			'tmp_name' => [
				0 => [
					'Test'
				]
			],
		];

		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $this->account->getId())
			->willReturn($this->account);
		$this->aliasesService->expects(self::never())
			->method('findByAliasAndUserId');
		$this->request->expects(self::once())
			->method('getUploadedFile')
			->willReturn($attachments);
		$this->attachmentService->expects(self::once())
			->method('addFile')
			->willThrowException(new UploadException());
		$this->attachmentService->expects(self::never())
			->method('deleteAttachment');
		$this->logger->expects(self::once())
			->method('error');
		$this->outboxService->expects(self::never())
			->method('saveMessage');
		$this->outboxService->expects(self::never())
			->method('sendMessage');

		$expected = new DataResponse('Could not handle attachments', Http::STATUS_INTERNAL_SERVER_ERROR);
		$actual = $this->controller->send(
			$this->accountId,
			$this->fromEmail,
			'',
			'',
			true,
			[]
		);

		$this->assertEquals($expected, $actual);
	}

	public function testAlias(): void {
		$aliasMail = 'john-alias@test.com';
		$accountId = 1;
		$alias = new Alias();
		$alias->setId($accountId);
		$alias->setName('John');
		$alias->setAccountId($accountId);
		$alias->setAlias($aliasMail);
		$mailAccount = new MailAccount();
		$mailAccount->setId($accountId);
		$mailAccount->setEmail($this->fromEmail);
		$account = new Account($mailAccount);
		$this->message->setStatus(LocalMessage::STATUS_PROCESSED);

		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $account->getId())
			->willReturn($account);
		$this->aliasesService->expects(self::once())
			->method('findByAliasAndUserId')
			->willReturn($alias);
		$this->request->expects(self::once())
			->method('getUploadedFile')
			->willReturn(null);
		$this->attachmentService->expects(self::never())
			->method('addFile');
		$this->attachmentService->expects(self::never())
			->method('deleteAttachment');
		$this->outboxService->expects(self::once())
			->method('saveMessage');
		$this->outboxService->expects(self::once())
			->method('sendMessage')
			->willReturn($this->message);

		$expected = new DataResponse('Success', Http::STATUS_OK);
		$actual = $this->controller->send(
			$accountId,
			$aliasMail,
			'',
			'',
			true,
			[]
		);

		$this->assertEquals($expected, $actual);
	}
	public function testNoAlias(): void {
		$aliasMail = 'john-alias@test.com';

		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $this->account->getId())
			->willReturn($this->account);
		$this->aliasesService->expects(self::once())
			->method('findByAliasAndUserId')
			->willThrowException(new DoesNotExistException(''));
		$this->logger->expects(self::once())
			->method('error');
		$this->request->expects(self::never())
			->method('getUploadedFile');
		$this->attachmentService->expects(self::never())
			->method('addFile');
		$this->attachmentService->expects(self::never())
			->method('deleteAttachment');
		$this->outboxService->expects(self::never())
			->method('saveMessage');
		$this->outboxService->expects(self::never())
			->method('sendMessage');

		$expected = new DataResponse(new DoesNotExistException(''), Http::STATUS_NOT_FOUND);
		$actual = $this->controller->send(
			$this->accountId,
			$aliasMail,
			'',
			'',
			true,
			[]
		);

		$this->assertEquals($expected, $actual);
	}

	public function testNoAccount(): void {
		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $this->accountId)
			->willThrowException(new ClientException());
		$this->logger->expects(self::once())
			->method('error');
		$this->aliasesService->expects(self::never())
			->method('findByAliasAndUserId');
		$this->request->expects(self::never())
			->method('getUploadedFile');
		$this->attachmentService->expects(self::never())
			->method('addFile');
		$this->attachmentService->expects(self::never())
			->method('deleteAttachment');
		$this->outboxService->expects(self::never())
			->method('saveMessage');
		$this->outboxService->expects(self::never())
			->method('sendMessage');

		$expected = new DataResponse(new ClientException(), Http::STATUS_NOT_FOUND);
		$actual = $this->controller->send(
			$this->accountId,
			$this->fromEmail,
			'',
			'',
			true,
			[]
		);

		$this->assertEquals($expected, $actual);
	}
}
