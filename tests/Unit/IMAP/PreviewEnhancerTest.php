<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DbMapper;
use OCA\Mail\IMAP\MessageMapper as ImapMapper;
use OCA\Mail\IMAP\PreviewEnhancer;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\Avatar\Avatar;
use OCA\Mail\Service\AvatarService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class PreviewEnhancerTest extends TestCase {

	/** @var ProtocolFactory|MockObject */
	private $protocolFactory;
	/** @var ImapMapper|MockObject */
	private $imapMapper;
	/** @var DbMapper|MockObject */
	private $dbMapper;
	/** @var LoggerInterface|MockObject */
	private $logger;
	/** @var AvatarService|MockObject */
	private $avatarService;
	/** @var PreviewEnhancer */
	private $previewEnhancer;
	/** @var AttachmentService|MockObject */
	private $attachmentService;

	protected function setUp(): void {
		parent::setUp();

		$this->protocolFactory = $this->createMock(ProtocolFactory::class);
		$this->imapMapper = $this->createMock(ImapMapper::class);
		$this->dbMapper = $this->createMock(DbMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->avatarService = $this->createMock(AvatarService::class);
		$this->attachmentService = $this->createMock(AttachmentService::class);

		$this->previewEnhancer = new PreviewEnhancer(
			$this->protocolFactory,
			$this->imapMapper,
			$this->dbMapper,
			$this->logger,
			$this->avatarService,
			$this->attachmentService
		);
	}

	public function testAvatars(): void {
		$account = $this->createStub(\OCA\Mail\Account::class);
		$mailbox = $this->createStub(\OCA\Mail\Db\Mailbox::class);
		$message1 = new Message();
		$message1->setId(1);
		$message1->setStructureAnalyzed(true);
		$message1->setFrom(new AddressList([Address::fromRaw('Alice', 'alice@example.com')]));
		$message2 = new Message();
		$message2->setId(2);
		$message2->setStructureAnalyzed(true);
		$message2->setFrom(new AddressList([Address::fromRaw('Bob', 'bob@example.com')]));
		$messages = [$message1, $message2];
		$message2Avatar = new Avatar('example.com', 'image/png', true);
		$this->attachmentService->expects($this->exactly(2))
			->method('getAttachmentNames')
			->withConsecutive(
				[$account, $mailbox, $message1],
				[$account, $mailbox, $message2],
			)
			->willReturnOnConsecutiveCalls(
				[], []
			);
		$this->avatarService->expects($this->exactly(2))
			->method('getCachedAvatar')
			->withConsecutive(
				['alice@example.com', 'testuser'],
				['bob@example.com', 'testuser']
			)
			->willReturnOnConsecutiveCalls(
				null,
				$message2Avatar
			);
		$this->previewEnhancer->process(
			$account,
			$mailbox,
			$messages,
			true,
			'testuser'
		);
		$this->assertTrue($message1->jsonSerialize()['fetchAvatarFromClient']);
		$this->assertFalse($message2->jsonSerialize()['fetchAvatarFromClient']);
		$this->assertNull($message1->getAvatar());
		$this->assertSame($message2Avatar, $message2->getAvatar());
	}

}
