<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Exception\AttachmentNotFoundException;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\GroupsIntegration;
use OCA\Mail\Service\TransmissionService;
use OCP\Files\SimpleFS\InMemoryFile;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class TransmissionServiceTest extends TestCase {

	private GroupsIntegration|MockObject $groupsIntegration;
	private AttachmentService|MockObject $attachmentService;
	private LoggerInterface|MockObject $logger;
	private MockObject|TransmissionService $transmissionService;

	protected function setUp(): void {
		parent::setUp();

		$this->attachmentService = $this->createMock(AttachmentService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->groupsIntegration = $this->createMock(GroupsIntegration::class);
		$this->transmissionService = new TransmissionService(
			$this->groupsIntegration,
			$this->attachmentService,
			$this->logger,
		);
	}

	public function testGetAddressList() {
		$expected = new AddressList([Address::fromRaw('Bob', 'bob@test.com')]);
		$recipient = new Recipient();
		$recipient->setLabel('Bob');
		$recipient->setEmail('bob@test.com');
		$recipient->setType(Recipient::TYPE_TO);
		$localMessage = new LocalMessage();
		$localMessage->setRecipients([$recipient]);

		$this->groupsIntegration->expects(self::once())
			->method('expand')
			->willReturn([$recipient]);

		$actual = $this->transmissionService->getAddressList($localMessage, Recipient::TYPE_TO);
		$this->assertEquals($expected, $actual);
	}

	public function testGetAttachments() {
		$id = 1;
		$expected = [[
			'type' => 'local',
			'id' => $id
		]];
		$attachment = new LocalAttachment();
		$attachment->setId($id);
		$localMessage = new LocalMessage();
		$localMessage->setAttachments([$attachment]);

		$actual = $this->transmissionService->getAttachments($localMessage);
		$this->assertEquals($expected, $actual);
	}

	public function testGetAttachmentContent(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$attachment = new LocalAttachment();
		$attachment->setFileName('test.txt');
		$attachment->setMimeType('text/plain');
		$attachment->setDisposition(LocalAttachment::DISPOSITION_ATTACHMENT);

		$file = new InMemoryFile(
			'test.txt',
			"Hello, I'm a test file."
		);

		$this->attachmentService->expects(self::once())
			->method('getAttachment')
			->willReturn([$attachment, $file]);
		$this->logger->expects(self::never())
			->method('warning');

		$content = $this->transmissionService->getAttachmentContent($account, ['id' => 1, 'type' => 'local']);

		$this->assertEquals([
			'content' => "Hello, I'm a test file.",
			'type' => 'text/plain',
			'name' => 'test.txt',
			'disposition' => 'attachment',
			'contentId' => null,
		], $content);
	}

	public function testGetAttachmentContentInlineWithContentId(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$attachment = new LocalAttachment();
		$attachment->setFileName('logo.png');
		$attachment->setMimeType('image/png');
		$attachment->setDisposition(LocalAttachment::DISPOSITION_INLINE);
		$attachment->setContentId('img001@example.com');

		$file = new InMemoryFile('logo.png', 'fake png content');

		$this->attachmentService->expects(self::once())
			->method('getAttachment')
			->willReturn([$attachment, $file]);

		$content = $this->transmissionService->getAttachmentContent($account, ['id' => 1, 'type' => 'local']);

		$this->assertEquals('fake png content', $content['content']);
		$this->assertEquals('inline', $content['disposition']);
		$this->assertEquals('img001@example.com', $content['contentId']);
		$this->assertEquals('logo.png', $content['name']);
	}

	public function testGetAttachmentContentNoId(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$this->logger->expects(self::once())
			->method('warning');

		$content = $this->transmissionService->getAttachmentContent($account, ['type' => 'local']);

		$this->assertNull($content);
	}

	public function testGetAttachmentContentNotFound(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);

		$this->attachmentService->expects(self::once())
			->method('getAttachment')
			->willThrowException(new AttachmentNotFoundException());
		$this->logger->expects(self::once())
			->method('warning');

		$content = $this->transmissionService->getAttachmentContent($account, ['id' => 1, 'type' => 'local']);

		$this->assertNull($content);
	}
}
