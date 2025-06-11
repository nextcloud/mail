<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Service;

use ChristophWurst\Nextcloud\Testing\TestUser;
use Horde_Imap_Client;
use OC;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\LocalAttachmentMapper;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Send\Chain;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\Attachment\AttachmentStorage;
use OCA\Mail\Service\OutboxService;
use OCA\Mail\Service\Sync\SyncService;
use OCA\Mail\Tests\Integration\Framework\ImapTest;
use OCA\Mail\Tests\Integration\Framework\ImapTestAccount;
use OCA\Mail\Tests\Integration\TestCase;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use OCP\IServerContainer;
use OCP\IUser;
use OCP\Server;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class OutboxServiceIntegrationTest extends TestCase {
	use ImapTest,
		ImapTestAccount,
		TestUser;

	/** @var MailAccount */
	private $account;

	/** @var IUser */
	private $user;

	/** @var IAttachmentService */
	private $attachmentService;

	/** @var OutboxService */
	private $outbox;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	/** @var IMAPClientFactory */
	private $clientFactory;

	/** @var LocalMessageMapper */
	private $mapper;

	/** @var Folder */
	private $userFolder;

	/** @var  */
	private $accountService;

	/** @var ITimeFactory */
	private $timeFactory;
	private \PHPUnit\Framework\MockObject\MockObject|Chain $chain;
	private \OCP\IDBConnection $db;

	protected function setUp(): void {
		parent::setUp();

		$this->resetImapAccount();
		$this->disconnectImapAccount();

		$this->user = $this->createTestUser();
		$this->account = $this->createTestAccount($this->user->getUID());
		$c = Server::get(ContainerInterface::class);
		$userContainer = $c->get(IServerContainer::class);
		$this->userFolder = $userContainer->getUserFolder($this->account->getUserId());
		$mailManager = Server::get(IMailManager::class);
		$this->attachmentService = new AttachmentService(
			$this->userFolder,
			Server::get(LocalAttachmentMapper::class),
			Server::get(AttachmentStorage::class),
			$mailManager,
			Server::get(\OCA\Mail\IMAP\MessageMapper::class),
			new NullLogger()
		);
		$this->client = $this->getClient($this->account);
		$this->mapper = Server::get(LocalMessageMapper::class);
		$this->eventDispatcher = Server::get(IEventDispatcher::class);
		$this->clientFactory = Server::get(IMAPClientFactory::class);
		$this->accountService = Server::get(AccountService::class);
		$this->timeFactory = Server::get(ITimeFactory::class);
		$this->chain = Server::get(Chain::class);

		$this->db = OC::$server->getDatabaseConnection();
		$qb = $this->db->getQueryBuilder();
		$delete = $qb->delete($this->mapper->getTableName());
		$delete->execute();

		$this->outbox = new OutboxService(
			$this->mapper,
			$this->attachmentService,
			$this->eventDispatcher,
			$this->clientFactory,
			$mailManager,
			$this->accountService,
			$this->timeFactory,
			$this->createMock(LoggerInterface::class),
			$this->chain
		);
	}

	public function testSaveAndGetMessage(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($this->account->getId());
		$message->setSubject('subject');
		$message->setBodyHtml('message');
		$message->setHtml(true);

		$to = [[
			'label' => 'Penny',
			'email' => 'library@stardewvalley.com'
		]];

		$saved = $this->outbox->saveMessage(new Account($this->account), $message, $to, [], []);
		$this->assertNotEmpty($message->getRecipients());
		$this->assertEmpty($message->getAttachments());

		$retrieved = $this->outbox->getMessage($message->getId(), $this->user->getUID());
		$this->assertNotEmpty($message->getRecipients());
		$this->assertEmpty($message->getAttachments());

		self::assertCount(1, $retrieved->getRecipients());
	}

	public function testSaveAndGetMessages(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($this->account->getId());
		$message->setSubject('subject');
		$message->setBodyHtml('message');
		$message->setHtml(true);

		$saved = $this->outbox->saveMessage(new Account($this->account), $message, [], [], []);
		$this->assertEmpty($message->getRecipients());
		$this->assertEmpty($message->getAttachments());

		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($this->account->getId());
		$message->setSubject('subject');
		$message->setBodyHtml('message');
		$message->setHtml(true);

		$saved = $this->outbox->saveMessage(new Account($this->account), $message, [], [], []);
		$this->assertEmpty($saved->getRecipients());
		$this->assertEmpty($saved->getAttachments());

		$messages = $this->outbox->getMessages($this->user->getUID());
		$this->assertCount(2, $messages);
	}

	public function testSaveAndGetMessageWithMessageAttachment(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($this->account->getId());
		$message->setSubject('subject');
		$message->setBodyHtml('message');
		$message->setHtml(true);

		/** @var \Horde_Imap_Client_Mailbox[] $mailBoxes */
		$mailBoxes = $this->getMailboxes();
		$inbox = null;
		foreach ($mailBoxes as $mailBox) {
			if ($mailBox->equals('INBOX')) {
				$inbox = $mailBox;
				break;
			}
		}
		$imapMessage = $this->getMessageBuilder()
			->from('buffington@domain.tld')
			->to('user@domain.tld')
			->finish();
		$newUid = $this->saveMessage($inbox->__toString(), $imapMessage, $this->account);
		/** @var MailboxMapper $mailBoxMapper */
		$mailBoxMapper = Server::get(MailboxMapper::class);
		$dbInbox = $mailBoxMapper->find(new Account($this->account), $inbox->__toString());
		/** @var SyncService $syncService */
		$syncService = Server::get(SyncService::class);
		$syncService->syncMailbox(
			new Account($this->account),
			$dbInbox,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
			false,
			null,
			[]
		);
		/** @var MessageMapper $messageMapper */
		$messageMapper = Server::get(MessageMapper::class);
		$dbMessages = $messageMapper->findByUids($dbInbox, [$newUid]);
		$attachments = [
			[
				'type' => 'message',
				'id' => $dbMessages[0]->getId(),
				'fileName' => 'embedded.msg'
			]
		];

		$saved = $this->outbox->saveMessage(new Account($this->account), $message, [], [], [], $attachments);
		$this->assertEmpty($saved->getRecipients());
		$this->assertNotEmpty($saved->getAttachments());
		$this->assertCount(1, $saved->getAttachments());

		$messages = $this->outbox->getMessages($this->user->getUID());
		$result = $messages[0];
		$attachments = $result->getAttachments();
		$attachment = $attachments[0];

		$this->assertCount(1, $messages);
		$this->assertNotEmpty($message->getAttachments());
		$this->assertCount(1, $attachments);
		$this->assertEquals('embedded.msg', $attachment->getFileName());
		$this->assertEquals($message->getId(), $attachment->getLocalMessageId());
	}

	public function testSaveAndGetMessageWithCloudAttachmentt(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($this->account->getId());
		$message->setSubject('subject');
		$message->setBodyHtml('message');
		$message->setHtml(true);
		$this->userFolder->newFile('/test.txt', file_get_contents(__DIR__ . '/../../data/test.txt'));
		$attachments = [
			[
				'type' => 'cloud',
				'fileName' => 'test.txt'
			]
		];

		$saved = $this->outbox->saveMessage(new Account($this->account), $message, [], [], [], $attachments);
		$this->assertEmpty($saved->getRecipients());
		$this->assertNotEmpty($saved->getAttachments());
		$this->assertCount(1, $saved->getAttachments());

		$messages = $this->outbox->getMessages($this->user->getUID());
		$result = $messages[0];
		$attachments = $result->getAttachments();
		$attachment = $attachments[0];

		$this->assertCount(1, $messages);
		$this->assertNotEmpty($message->getAttachments());
		$this->assertCount(1, $attachments);
		$this->assertEquals('test.txt', $attachment->getFileName());
		$this->assertEquals($message->getId(), $attachment->getLocalMessageId());
	}

	public function testSaveAndDeleteMessage(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($this->account->getId());
		$message->setSubject('subject');
		$message->setBodyHtml('message');
		$message->setHtml(true);

		$to = [[
			'label' => 'Penny',
			'email' => 'library@stardewvalley.com'
		]];

		$saved = $this->outbox->saveMessage(new Account($this->account), $message, $to, [], []);
		$this->assertNotEmpty($message->getRecipients());
		$this->assertEmpty($message->getAttachments());

		$this->outbox->deleteMessage($this->user->getUID(), $saved);

		$this->expectException(DoesNotExistException::class);
		$this->outbox->getMessage($message->getId(), $this->user->getUID());
	}

	public function testSaveAndUpdateMessage(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($this->account->getId());
		$message->setSubject('subject');
		$message->setBodyHtml('message');
		$message->setHtml(true);

		$to = [[
			'label' => 'Penny',
			'email' => 'library@stardewvalley.com'
		]];

		$saved = $this->outbox->saveMessage(new Account($this->account), $message, $to, [], []);
		$this->assertNotEmpty($message->getRecipients());
		$this->assertCount(1, $saved->getRecipients());
		$this->assertEmpty($message->getAttachments());

		$saved->setSubject('Your Trailer will be put up for sale');
		$cc = [[
			'label' => 'Pam',
			'email' => 'buyMeABeer@stardewvalley.com'
		]];
		$updated = $this->outbox->updateMessage(new Account($this->account), $saved, $to, $cc, []);

		$this->assertNotEmpty($updated->getRecipients());
		$this->assertEquals('Your Trailer will be put up for sale', $updated->getSubject());
		$this->assertCount(2, $updated->getRecipients());
	}

	public function testSaveAndSendMessage(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($this->account->getId());
		$message->setSubject('subject');
		$message->setBodyHtml('message');
		$message->setHtml(true);

		$to = [[
			'label' => 'Penny',
			'email' => 'library@stardewvalley.com'
		]];

		$saved = $this->outbox->saveMessage(new Account($this->account), $message, $to, [], []);
		$this->assertNotEmpty($message->getRecipients());
		$this->assertCount(1, $saved->getRecipients());
		$this->assertEmpty($message->getAttachments());

		$actual = $this->outbox->sendMessage($saved, new Account($this->account));

		$this->expectException(DoesNotExistException::class);
		$this->outbox->getMessage($message->getId(), $this->user->getUID());
	}

	public function testSaveAndFlush(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($this->account->getId());
		$message->setSubject('subject');
		$message->setBodyHtml('message');
		$message->setHtml(true);
		$message->setSendAt(100);

		$to = [[
			'label' => 'Penny',
			'email' => 'library@stardewvalley.com'
		]];

		$saved = $this->outbox->saveMessage(new Account($this->account), $message, $to, [], []);
		$this->assertNotEmpty($saved->getRecipients());
		$this->assertEmpty($saved->getAttachments());

		$this->outbox->flush();
		$this->expectException(DoesNotExistException::class);
		$this->outbox->getMessage($saved->getId(), $this->user->getUID());
	}
}
