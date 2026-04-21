<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\JMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use JmapClient\Requests\Mail\MailParameters as MailParametersRequest;
use JmapClient\Responses\Mail\MailIdentityParameters;
use JmapClient\Responses\ResponseException;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\JMAP\Exception\JmapException;
use OCA\Mail\JMAP\JmapTransmissionConnector;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\JMAP\JmapOperationsService;
use OCA\Mail\Service\TransmissionService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class JmapTransmissionConnectorTest extends TestCase {

	private JmapOperationsService|MockObject $jmapOperationsService;
	private TransmissionService|MockObject $transmissionService;
	private AliasesService|MockObject $aliasesService;
	private MailboxMapper|MockObject $mailboxMapper;
	private LoggerInterface|MockObject $logger;
	private JmapTransmissionConnector $connector;

	protected function setUp(): void {
		parent::setUp();

		$this->jmapOperationsService = $this->createMock(JmapOperationsService::class);
		$this->transmissionService = $this->createMock(TransmissionService::class);
		$this->aliasesService = $this->createMock(AliasesService::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->connector = new JmapTransmissionConnector(
			$this->jmapOperationsService,
			$this->transmissionService,
			$this->aliasesService,
			$this->mailboxMapper,
			$this->logger,
		);
	}

	public function testSaveMessageIncludesAttachment(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$mailAccount->setName('Bob');
		$mailAccount->setEmail('bob@example.com');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setId(9);
		$mailbox->setRemoteId('mbox-remote-1');

		$message = new LocalMessage();
		$message->setSubject('Test');
		$message->setBodyPlain('body');
		$message->setRecipients([]);

		$attachmentRef = ['type' => 'local', 'id' => 1];
		$attachmentContent = [
			'content' => 'file content',
			'type' => 'text/plain',
			'name' => 'test.txt',
			'disposition' => 'attachment',
			'contentId' => null,
		];

		$this->transmissionService->method('getAddressList')->willReturn(new AddressList([]));
		$this->transmissionService->expects(self::once())
			->method('getAttachments')
			->with($message)
			->willReturn([$attachmentRef]);
		$this->transmissionService->expects(self::once())
			->method('getAttachmentContent')
			->with($account, $attachmentRef)
			->willReturn($attachmentContent);

		$this->jmapOperationsService->expects(self::once())
			->method('connect')
			->with($account);

		$savedAttachments = null;
		$this->jmapOperationsService->expects(self::once())
			->method('entitySave')
			->willReturnCallback(function (MailParametersRequest $emailParams, array $attachments) use (&$savedAttachments) {
				$savedAttachments = $attachments;
				return 'remote-id';
			});

		$this->connector->saveMessage($account, $mailbox, $message, ['$draft']);

		$this->assertEquals([$attachmentContent], $savedAttachments);
	}

	public function testSaveMessageSkipsMissingAttachment(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$mailAccount->setName('Bob');
		$mailAccount->setEmail('bob@example.com');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setId(9);
		$mailbox->setRemoteId('mbox-remote-1');

		$message = new LocalMessage();
		$message->setSubject('Test');
		$message->setBodyPlain('body');
		$message->setRecipients([]);

		$attachmentRef = ['type' => 'local', 'id' => 1];

		$this->transmissionService->method('getAddressList')->willReturn(new AddressList([]));
		$this->transmissionService->expects(self::once())
			->method('getAttachments')
			->willReturn([$attachmentRef]);
		$this->transmissionService->expects(self::once())
			->method('getAttachmentContent')
			->willReturn(null);

		$savedAttachments = null;
		$this->jmapOperationsService->expects(self::once())
			->method('entitySave')
			->willReturnCallback(function (MailParametersRequest $emailParams, array $attachments) use (&$savedAttachments) {
				$savedAttachments = $attachments;
				return 'remote-id';
			});

		$this->connector->saveMessage($account, $mailbox, $message, ['$draft']);

		$this->assertEquals([], $savedAttachments);
	}

	private function prepareSend(): array {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$mailAccount->setName('Bob');
		$mailAccount->setEmail('bob@example.com');
		$mailAccount->setDraftsMailboxId(5);
		$account = new Account($mailAccount);

		$sentMailbox = new Mailbox();
		$sentMailbox->setId(6);
		$sentMailbox->setRemoteId('sent-remote');
		$draftsMailbox = new Mailbox();
		$draftsMailbox->setId(5);
		$draftsMailbox->setRemoteId('drafts-remote');

		$message = new LocalMessage();
		$message->setSubject('Test');
		$message->setBodyPlain('body');
		$message->setStatus(LocalMessage::STATUS_RAW);

		$this->transmissionService->method('getAddressList')
			->willReturnCallback(static fn (LocalMessage $message, int $type) => $type === Recipient::TYPE_TO
				? new AddressList([Address::fromRaw('Alice', 'alice@example.com')])
				: new AddressList([]));
		$this->transmissionService->method('getAttachments')->willReturn([]);
		$this->mailboxMapper->method('findById')->with(5)->willReturn($draftsMailbox);
		$this->jmapOperationsService->method('identityFetch')
			->willReturn([new MailIdentityParameters(['id' => 'identity-1', 'email' => 'bob@example.com'])]);

		return [$account, $message, $sentMailbox];
	}

	public function testSendMessage(): void {
		[$account, $message, $sentMailbox] = $this->prepareSend();
		$this->jmapOperationsService->expects(self::once())
			->method('entitySend')
			->with('identity-1', self::isInstanceOf(MailParametersRequest::class), 'drafts-remote', 'sent-remote', 'bob@example.com', ['alice@example.com'], []);

		$this->connector->sendMessage($account, $message, $sentMailbox);

		$this->assertSame(LocalMessage::STATUS_PROCESSED, $message->getStatus());
	}

	public static function sendFailureProvider(): array {
		return [
			'transient server error' => [new JmapException('', ResponseException::SERVER_UNAVAILABLE), LocalMessage::STATUS_SMPT_SEND_FAIL],
			'rate limit' => [new JmapException('', 'rateLimit'), LocalMessage::STATUS_RATELIMIT],
			'too many recipients' => [new JmapException('', 'tooManyRecipients'), LocalMessage::STATUS_TOO_MANY_RECIPIENTS],
			'permanent rejection' => [new JmapException('', 'forbiddenToSend'), LocalMessage::STATUS_ERROR],
			'unknown error type' => [new JmapException('', 'somethingNew'), LocalMessage::STATUS_ERROR],
			'delivery uncertain' => [new JmapException('', ResponseException::SERVER_FAIL, true), LocalMessage::STATUS_ERROR],
			'not submitted' => [new ServiceException('upload failed'), LocalMessage::STATUS_SMPT_SEND_FAIL],
		];
	}

	/**
	 * @dataProvider sendFailureProvider
	 */
	public function testSendMessageFailureStatus(ServiceException $exception, int $expectedStatus): void {
		[$account, $message, $sentMailbox] = $this->prepareSend();
		$this->jmapOperationsService->expects(self::once())
			->method('entitySend')
			->willThrowException($exception);
		$this->logger->expects(self::once())
			->method('error');

		$this->connector->sendMessage($account, $message, $sentMailbox);

		$this->assertSame($expectedStatus, $message->getStatus());
	}

	public function testSendMessageAddsAiGeneratedHeader(): void {
		[$account, $message, $sentMailbox] = $this->prepareSend();
		$message->setAiGenerated(true);
		$sentParameters = null;
		$this->jmapOperationsService->expects(self::once())
			->method('entitySend')
			->willReturnCallback(function (string $identity, MailParametersRequest $parameters) use (&$sentParameters) {
				$sentParameters = $parameters;
			});

		$this->connector->sendMessage($account, $message, $sentMailbox);

		$sentParameters->bind($wire);
		$this->assertSame(' 1', $wire->{'header:' . LocalMessage::HEADER_AI_GENERATED . ':asRaw'} ?? null);
	}
}
