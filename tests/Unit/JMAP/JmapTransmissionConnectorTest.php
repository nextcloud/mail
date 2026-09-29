<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\JMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use JmapClient\Requests\Mail\MailParameters as MailParametersRequest;
use OCA\Mail\Account;
use OCA\Mail\AddressList;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
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
}
