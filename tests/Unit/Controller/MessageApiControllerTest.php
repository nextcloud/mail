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
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\SmimeDecryptException;
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
use OCA\Mail\Service\Search\MailSearch;
use OCA\Mail\Service\TrustedSenderService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IMimeTypeDetector;
use OCP\IRequest;
use OCP\IURLGenerator;
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
	private IUserManager|MockObject $userManager;
	private TrustedSenderService|MockObject $trustedSenderService;
	private MailManager|MockObject $mailManager;
	private MailSearch|MockObject $mailSearch;
	private MockObject|IURLGenerator $urlGenerator;
	private MockObject|IMimeTypeDetector $mimeTypeDetector;
	private IMAPClientFactory|MockObject $imapClientFactory;
	private DkimService|MockObject $dkimService;
	private MockObject|ItineraryService $itineraryService;
	private int $messageId = 100;
	private int $mailboxId = 42;



	protected function setUp(): void {
		parent::setUp();

		$this->appName = 'mail';
		$this->service = $this->createMock(OutboxService::class);
		$this->userId = 'john';
		$this->request = $this->createMock(IRequest::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->aliasesService = $this->createMock(AliasesService::class);
		$this->attachmentService = $this->createMock(AttachmentService::class);
		$this->outboxService = $this->createMock(OutboxService::class);
		$this->mailManager = $this->createMock(MailManager::class);
		$this->mailSearch = $this->createMock(MailSearch::class);
		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->mimeTypeDetector = $this->createMock(IMimeTypeDetector::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->attachmentService = $this->createMock(AttachmentService::class);
		$this->outboxService = $this->createMock(OutboxService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->time = $this->createMock(ITimeFactory::class);
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
			$this->mailSearch,
			$this->mailManager,
			$this->imapClientFactory,
			$this->logger,
			$this->time,
			$this->urlGenerator,
			$this->mimeTypeDetector,
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
		$this->message->setBody('');
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
					'smime' => new SmimeData(),
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
					'smime' => $smime1,
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
					'smime' => $smime2,
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
			'smime' => $smime,
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
}
