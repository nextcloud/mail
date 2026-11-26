<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DbMapper;
use OCA\Mail\IMAP\MessageMapper as ImapMapper;
use OCA\Mail\IMAP\MessageStructureData;
use OCA\Mail\IMAP\PreviewEnhancer;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\Avatar\Avatar;
use OCA\Mail\Service\AvatarService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class PreviewEnhancerTest extends TestCase {

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

	public function testAnalyzesStructureBeforeLoadingAttachmentNames(): void {
		[$account, $mailbox] = $this->createImapAccountAndMailbox();
		$message = $this->createMessage(7);
		$this->imapMapper->method('getBodyStructureData')
			->willReturn([7 => new MessageStructureData(false, 'Preview', false, false, false)]);
		$this->attachmentService->expects($this->once())
			->method('getAttachmentNames')
			->with($account, $mailbox, $this->callback(static fn (Message $message): bool => $message->getStructureAnalyzed()));

		$this->previewEnhancer->process($account, $mailbox, [$message]);
	}

	public function testAnalyzesStructureInChunks(): void {
		[$account, $mailbox] = $this->createImapAccountAndMailbox();
		$uids = range(1, 2 * PreviewEnhancer::CHUNK_SIZE + 1);
		$messages = array_map(fn (int $uid): Message => $this->createMessage($uid), $uids);
		$chunks = [];
		$this->imapMapper->method('getBodyStructureData')
			->willReturnCallback(static function (Horde_Imap_Client_Socket $client, string $mailbox, array $uids) use (&$chunks): array {
				$chunks[] = $uids;
				return [];
			});

		$result = $this->previewEnhancer->process($account, $mailbox, $messages);

		$this->assertSame(array_chunk($uids, PreviewEnhancer::CHUNK_SIZE), $chunks);
		$this->assertSame($messages, $result);
	}

	public function testKeepsAnalyzedChunksWhenALaterChunkFails(): void {
		[$account, $mailbox] = $this->createImapAccountAndMailbox();
		$messages = array_map(fn (int $uid): Message => $this->createMessage($uid), range(1, PreviewEnhancer::CHUNK_SIZE + 1));
		$this->imapMapper->method('getBodyStructureData')
			->willReturnCallback(static fn (Horde_Imap_Client_Socket $client, string $mailbox, array $uids): array => match ($uids[0]) {
				1 => array_fill_keys($uids, new MessageStructureData(false, 'Preview', false, false, false)),
				PreviewEnhancer::CHUNK_SIZE + 1 => throw new Horde_Imap_Client_Exception('connection lost'),
			});
		$this->dbMapper->expects($this->once())
			->method('updatePreviewDataBulk')
			->with(...array_slice($messages, 0, PreviewEnhancer::CHUNK_SIZE));

		$this->previewEnhancer->process($account, $mailbox, $messages);

		$this->assertFalse($messages[PreviewEnhancer::CHUNK_SIZE]->getStructureAnalyzed());
	}

	public function testMarksOnlyUnanalyzedJmapMessagesAsAnalyzed(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setProtocol(MailAccount::PROTOCOL_JMAP);
		$account = new Account($mailAccount);
		$mailbox = new Mailbox();
		$analyzed = $this->createMessage(1, true);
		$unanalyzed = $this->createMessage(2);
		$this->imapMapper->expects($this->never())
			->method('getBodyStructureData');
		$this->dbMapper->expects($this->once())
			->method('updatePreviewDataBulk')
			->with($unanalyzed);

		$this->previewEnhancer->process($account, $mailbox, [$analyzed, $unanalyzed]);

		$this->assertTrue($unanalyzed->getStructureAnalyzed());
	}

	/**
	 * @return array{Account, Mailbox}
	 */
	private function createImapAccountAndMailbox(): array {
		$mailAccount = new MailAccount();
		$mailAccount->setEmail('user@example.com');
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$this->protocolFactory->method('imapClient')
			->willReturn($this->createStub(Horde_Imap_Client_Socket::class));
		return [new Account($mailAccount), $mailbox];
	}

	private function createMessage(int $uid, bool $structureAnalyzed = false): Message {
		$message = new Message();
		$message->setUid($uid);
		$message->setStructureAnalyzed($structureAnalyzed);
		return $message;
	}
}
