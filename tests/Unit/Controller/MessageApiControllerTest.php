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
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\SmimeDecryptException;
use OCA\Mail\Exception\UploadException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Model\SmimeData;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\DkimService;
use OCA\Mail\Service\ItineraryService;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Service\OutboxService;
use OCA\Mail\Service\TrustedSenderService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\IRequest;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class MessageApiControllerTest extends TestCase {
	private string $appName = 'mail';
	private string $userId = 'john';
	private MockObject|IRequest $request;
	private MockObject|AccountService $accountService;
	private AliasesService|MockObject $aliasesService;
	private AttachmentService|MockObject $attachmentService;
	private OutboxService|MockObject $outboxService;
	private MailManager|MockObject $mailManager;
	private IMAPClientFactory|MockObject $imapClientFactory;
	private LoggerInterface|MockObject $logger;
	private MockObject|ITimeFactory $time;
	private MockObject|IURLGenerator $urlGenerator;
	private DkimService|MockObject $dkimService;
	private MockObject|ItineraryService $itineraryService;
	private TrustedSenderService|MockObject $trustedSenderService;
	private MessageApiController $controller;
	private string $fromEmail = 'john@test.com';
	private int $accountId = 1;
	private Account $account;
	private LocalMessage $message;
	private int $messageId = 100;
	private int $mailboxId = 42;



	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->aliasesService = $this->createMock(AliasesService::class);
		$this->attachmentService = $this->createMock(AttachmentService::class);
		$this->outboxService = $this->createMock(OutboxService::class);
		$this->mailManager = $this->createMock(MailManager::class);
		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->time = $this->createMock(ITimeFactory::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->dkimService = $this->createMock(DkimService::class);
		$this->itineraryService = $this->createMock(ItineraryService::class);
		$this->trustedSenderService = $this->createMock(TrustedSenderService::class);

		$this->controller = new MessageApiController($this->appName,
			$this->userId,
			$this->request,
			$this->accountService,
			$this->aliasesService,
			$this->attachmentService,
			$this->outboxService,
			$this->mailManager,
			$this->imapClientFactory,
			$this->logger,
			$this->time,
			$this->urlGenerator,
			$this->dkimService,
			$this->itineraryService,
			$this->trustedSenderService,
		);

		$mailAccount = new MailAccount();
		$mailAccount->setId($this->accountId);
		$mailAccount->setEmail($this->fromEmail);
		$this->account = new Account($mailAccount);
		$this->message = new LocalMessage();
		$this->message->setAccountId($this->accountId);
		$this->message->setSubject('');
		$this->message->setBodyHtml('');
		$this->message->setHtml(true);
		$this->message->setType(LocalMessage::TYPE_OUTGOING);
	}

	/**
	 * @dataProvider getDataProvider
	 */
	public function testGet(bool $encrypted, bool $signed, array $json): void {
		$message = new Message();
		$message->setId($this->messageId);
		$message->setMailboxId($this->mailboxId);
		$message->setUid(1);
		$mailbox = new Mailbox();
		$mailbox->setAccountId($this->accountId);
		$client = $this->createMock(\Horde_Imap_Client_Socket::class);
		$imapMessage = $this->createMock(IMAPMessage::class);

		$this->logger->expects(self::never())
			->method('warning');
		$this->logger->expects(self::never())
			->method('error');
		$this->mailManager->expects(self::once())
			->method('getMessage')
			->with($this->userId, $this->messageId)
			->willReturn($message);
		$this->mailManager->expects(self::once())
			->method('getMailbox')
			->with($this->userId, $this->mailboxId)
			->willReturn($mailbox);
		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $this->accountId)
			->willReturn($this->account);
		$this->imapClientFactory->expects(self::once())
			->method('getClient')
			->willReturn($client);
		$this->mailManager->expects(self::once())
			->method('getImapMessage')
			->willReturn($imapMessage);
		$client->expects(self::once())
			->method('logout');
		$imapMessage->expects(self::once())
			->method('getFullMessage')
			->with($this->messageId, true)
			->willReturn(['id' => $this->messageId, 'attachments' => []]);
		$this->itineraryService->expects(self::once())
			->method('getCached')
			->willReturn(null);
		$this->trustedSenderService->expects(self::once())
			->method('isSenderTrusted')
			->willReturn(false);
		$imapMessage->expects(self::once())
			->method('isEncrypted')
			->willReturn($encrypted);
		$imapMessage->expects(self::once())
			->method('isSigned')
			->willReturn($signed);
		if ($signed) {
			$imapMessage->expects(self::once())
				->method('isSignatureValid')
				->willReturn(true);
		}
		$this->dkimService->expects(self::once())
			->method('getCached')
			->willReturn(null);
		$this->urlGenerator->expects(self::once())
			->method('linkToOCSRouteAbsolute')
			->willReturn('http://rawUrl');

		$expected = new DataResponse($json, Http::STATUS_OK);
		$actual = $this->controller->get($this->messageId);

		$this->assertEquals($expected, $actual);
	}

	public function getDataProvider(): array {
		$smime1 = new SmimeData();
		$smime1->setIsEncrypted(true);
		$smime1->setIsSigned(false);
		$smime2 = new SmimeData();
		$smime2->setIsEncrypted(false);
		$smime2->setIsSigned(true);
		$smime2->setSignatureIsValid(true);
		return [
			[
				'encrypted' => false,
				'signed' => false,
				'json' => [
					'attachments' => [],
					'id' => $this->messageId,
					'isSenderTrusted' => false,
					'smime' => (new SmimeData())->jsonSerialize(),
					'rawUrl' => 'http://rawUrl',
				]
			],
			[
				'encrypted' => true,
				'signed' => false,
				'json' => [
					'attachments' => [],
					'id' => $this->messageId,
					'isSenderTrusted' => false,
					'smime' => $smime1->jsonSerialize(),
					'rawUrl' => 'http://rawUrl',
				]
			],
			[
				'encrypted' => false,
				'signed' => true,
				'json' => [
					'attachments' => [],
					'id' => $this->messageId,
					'isSenderTrusted' => false,
					'smime' => $smime2->jsonSerialize(),
					'rawUrl' => 'http://rawUrl',
				]
			]
		];
	}

	public function testGetWithSmimeEncryptionFailed(): void {
		$message = new Message();
		$message->setId($this->messageId);
		$message->setMailboxId($this->mailboxId);
		$message->setEncrypted(true);
		$message->setUid(1);
		$mailbox = new Mailbox();
		$mailbox->setAccountId($this->accountId);
		$client = $this->createMock(\Horde_Imap_Client_Socket::class);
		$imapMessage = $this->createMock(IMAPMessage::class);
		$smime = new SmimeData();
		$smime->setIsEncrypted(true);
		$json = [
			'attachments' => [],
			'id' => $this->messageId,
			'isSenderTrusted' => false,
			'smime' => $smime->jsonSerialize(),
			'rawUrl' => 'http://rawUrl',
		];

		$this->logger->expects(self::once())
			->method('warning');
		$this->logger->expects(self::never())
			->method('error');
		$this->mailManager->expects(self::once())
			->method('getMessage')
			->with($this->userId, $this->messageId)
			->willReturn($message);
		$this->mailManager->expects(self::once())
			->method('getMailbox')
			->with($this->userId, $this->mailboxId)
			->willReturn($mailbox);
		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $this->accountId)
			->willReturn($this->account);
		$this->imapClientFactory->expects(self::once())
			->method('getClient')
			->willReturn($client);
		$this->mailManager->expects(self::exactly(2))
			->method('getImapMessage')
			->willReturnCallback(function ($client, $account, $mailbox, $uid, $loadBody) use ($imapMessage) {
				if ($loadBody) {
					throw new SmimeDecryptException();
				}
				return $imapMessage;
			});
		$client->expects(self::once())
			->method('logout');
		$imapMessage->expects(self::once())
			->method('getFullMessage')
			->with($this->messageId, false)
			->willReturn(['id' => $this->messageId, 'attachments' => []]);
		$this->itineraryService->expects(self::once())
			->method('getCached')
			->willReturn(null);
		$this->trustedSenderService->expects(self::once())
			->method('isSenderTrusted')
			->willReturn(false);
		$imapMessage->expects(self::once())
			->method('isSigned')
			->willReturn(false);
		$imapMessage->expects(self::never())
			->method('isSignatureValid');
		$this->dkimService->expects(self::once())
			->method('getCached')
			->willReturn(null);
		$this->urlGenerator->expects(self::once())
			->method('linkToOCSRouteAbsolute')
			->willReturn('http://rawUrl');

		$expected = new DataResponse($json, Http::STATUS_PARTIAL_CONTENT);
		$actual = $this->controller->get($this->messageId);

		$this->assertEquals($expected, $actual);
	}

	public function testGetWithSmimeException(): void {
		$message = new Message();
		$message->setId($this->messageId);
		$message->setMailboxId($this->mailboxId);
		$message->setEncrypted(true);
		$message->setUid(1);
		$mailbox = new Mailbox();
		$mailbox->setAccountId($this->accountId);
		$client = $this->createMock(\Horde_Imap_Client_Socket::class);

		$this->logger->expects(self::never())
			->method('warning');
		$this->logger->expects(self::once())
			->method('error');
		$this->mailManager->expects(self::once())
			->method('getMessage')
			->with($this->userId, $this->messageId)
			->willReturn($message);
		$this->mailManager->expects(self::once())
			->method('getMailbox')
			->with($this->userId, $this->mailboxId)
			->willReturn($mailbox);
		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $this->accountId)
			->willReturn($this->account);
		$this->imapClientFactory->expects(self::once())
			->method('getClient')
			->willReturn($client);
		$this->mailManager->expects(self::once())
			->method('getImapMessage')
			->willThrowException(new ServiceException());
		$client->expects(self::once())
			->method('logout');
		$this->itineraryService->expects(self::never())
			->method('getCached');
		$this->trustedSenderService->expects(self::never())
			->method('isSenderTrusted');
		$this->dkimService->expects(self::never())
			->method('getCached');
		$this->urlGenerator->expects(self::never())
			->method('linkToOCSRouteAbsolute');

		$expected = new DataResponse('Could not connect to IMAP server. Please check your logs.', Http::STATUS_INTERNAL_SERVER_ERROR);
		$actual = $this->controller->get($this->messageId);

		$this->assertEquals($expected, $actual);
	}

	public function testMailboxNotFound(): void {
		$message = new Message();
		$message->setId($this->messageId);
		$message->setMailboxId($this->mailboxId);
		$message->setEncrypted(true);
		$message->setUid(1);

		$this->logger->expects(self::never())
			->method('warning');
		$this->logger->expects(self::once())
			->method('error');
		$this->mailManager->expects(self::once())
			->method('getMessage')
			->with($this->userId, $this->messageId)
			->willReturn($message);
		$this->mailManager->expects(self::once())
			->method('getMailbox')
			->with($this->userId, $this->mailboxId)
			->willThrowException(new ClientException(''));
		$this->accountService->expects(self::never())
			->method('find');
		$this->imapClientFactory->expects(self::never())
			->method('getClient');
		$this->mailManager->expects(self::never())
			->method('getImapMessage');
		$this->itineraryService->expects(self::never())
			->method('getCached');
		$this->trustedSenderService->expects(self::never())
			->method('isSenderTrusted');
		$this->dkimService->expects(self::never())
			->method('getCached');
		$this->urlGenerator->expects(self::never())
			->method('linkToOCSRouteAbsolute');

		$expected = new DataResponse('Account not found.', Http::STATUS_NOT_FOUND);
		$actual = $this->controller->get($this->messageId);

		$this->assertEquals($expected, $actual);
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
			[['email' => 'john@test.com']]
		);

		$this->assertEquals($expected, $actual);
	}

	public function testSendNoRecipient(): void {
		$this->message->setStatus(LocalMessage::STATUS_RAW);

		$this->accountService->expects(self::once())
			->method('find')
			->with($this->userId, $this->account->getId())
			->willReturn($this->account);
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
			->method('sendMessage')
			->willReturn($this->message);

		$expected = new DataResponse('Recipients cannot be empty.', Http::STATUS_BAD_REQUEST);
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
			[LocalMessage::STATUS_PROCESSED, new DataResponse('', Http::STATUS_OK)],
			[LocalMessage::STATUS_NO_SENT_MAILBOX, new DataResponse('Configuration error: Cannot send message without sent mailbox.', Http::STATUS_FORBIDDEN)],
			[LocalMessage::STATUS_SMPT_SEND_FAIL, new DataResponse('SMTP error: could not send message. Message sending will be retried. Please check the logs.', Http::STATUS_INTERNAL_SERVER_ERROR)],
			[LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL, new DataResponse('Email was sent but could not be copied to sent mailbox. Copying will be retried. Please check the logs.', Http::STATUS_ACCEPTED)],
			[LocalMessage::STATUS_TOO_MANY_RECIPIENTS, new DataResponse('An error occured. Please check the logs.', Http::STATUS_INTERNAL_SERVER_ERROR)],
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
			[['email' => 'john@test.com']]
		);

		$this->assertEquals($expected, $actual);
	}

	public function exceptionData() {
		return [
			[new ServiceException(), new DataResponse('Processing error: could not send message. Please check the logs', Http::STATUS_BAD_REQUEST)],
			[new Exception(), new DataResponse('Fatal SMTP error: could not send message, and no resending is possible. Please check the mail server logs.', Http::STATUS_INTERNAL_SERVER_ERROR)],
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

		$expected = new DataResponse('', Http::STATUS_OK);
		$actual = $this->controller->send(
			$this->accountId,
			$this->fromEmail,
			'',
			'',
			true,
			[['email' => 'test@test.com']]
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

		$expected = new DataResponse('Could not convert attachment(s) to local attachment(s). Please check the logs.', Http::STATUS_INTERNAL_SERVER_ERROR);
		$actual = $this->controller->send(
			$this->accountId,
			$this->fromEmail,
			'',
			'',
			true,
			[['email' => 'john@test.com']]
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

		$expected = new DataResponse('', Http::STATUS_OK);
		$actual = $this->controller->send(
			$accountId,
			$aliasMail,
			'',
			'',
			true,
			[['email' => 'john@test.com']]
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

		$expected = new DataResponse("Could not find alias $aliasMail. Please check the logs.", Http::STATUS_NOT_FOUND);
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

		$expected = new DataResponse('Account not found.', Http::STATUS_NOT_FOUND);
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
