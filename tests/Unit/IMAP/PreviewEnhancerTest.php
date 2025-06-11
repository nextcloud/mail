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
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper as ImapMapper;
use OCA\Mail\IMAP\PreviewEnhancer;
use OCA\Mail\Service\Avatar\Avatar;
use OCA\Mail\Service\AvatarService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class PreviewEnhancerTest extends TestCase {


	/** @var IMAPClientFactory|MockObject */
	private $imapClientFactory;
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

	protected function setUp(): void {
		parent::setUp();

		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->imapMapper = $this->createMock(ImapMapper::class);
		$this->dbMapper = $this->createMock(DbMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->avatarService = $this->createMock(AvatarService::class);

		$this->previewEnhancer = new previewEnhancer($this->imapClientFactory,
			$this->imapMapper,
			$this->dbMapper,
			$this->logger,
			$this->avatarService);
	}

	public function testAvatars(): void {

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
			$this->createMock(\OCA\Mail\Account::class),
			$this->createMock(\OCA\Mail\Db\Mailbox::class),
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
