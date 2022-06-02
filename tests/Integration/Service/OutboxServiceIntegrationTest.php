<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Tests\Integration\Service;

use ChristophWurst\Nextcloud\Testing\TestUser;
use Horde_Imap_Client;
use OC;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\LocalAttachmentMapper;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
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

	/** @var IMailTransmission */
	private $transmission;

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

	protected function setUp(): void {
		parent::setUp();

		$this->resetImapAccount();
		$this->disconnectImapAccount();

		$this->user = $this->createTestUser();
		$this->account = $this->createTestAccount($this->user->getUID());
		$c = OC::$server->get(ContainerInterface::class);
		$userContainer = $c->get(IServerContainer::class);
		$this->userFolder = $userContainer->getUserFolder($this->account->getUserId());
		$mailManager = OC::$server->get(IMailManager::class);
		$this->attachmentService = new AttachmentService(
			$this->userFolder,
			OC::$server->get(LocalAttachmentMapper::class),
			OC::$server->get(AttachmentStorage::class),
			$mailManager,
			OC::$server->get(\OCA\Mail\IMAP\MessageMapper::class),
			new NullLogger()
		);
		$this->client = $this->getClient($this->account);
		$this->mapper = OC::$server->get(LocalMessageMapper::class);
		$this->transmission = OC::$server->get(IMailTransmission::class);
		$this->eventDispatcher = OC::$server->get(IEventDispatcher::class);
		$this->clientFactory = OC::$server->get(IMAPClientFactory::class);
		$this->accountService = OC::$server->get(AccountService::class);
		$this->timeFactory = OC::$server->get(ITimeFactory::class);

		$this->db = \OC::$server->getDatabaseConnection();
		$qb = $this->db->getQueryBuilder();
		$delete = $qb->delete($this->mapper->getTableName());
		$delete->execute();

		$this->outbox = new OutboxService(
			$this->transmission,
			$this->mapper,
			$this->attachmentService,
			$this->eventDispatcher,
			$this->clientFactory,
			$mailManager,
			$this->accountService,
			$this->timeFactory,
			$this->createMock(LoggerInterface::class)
		);
	}

	public function testSaveAndGetMessage(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($this->account->getId());
		$message->setSubject('subject');
		$message->setBody('message');
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
		$message->setBody('message');
		$message->setHtml(true);

		$saved = $this->outbox->saveMessage(new Account($this->account), $message, [], [], []);
		$this->assertEmpty($message->getRecipients());
		$this->assertEmpty($message->getAttachments());

		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($this->account->getId());
		$message->setSubject('subject');
		$message->setBody('message');
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
		$message->setBody('message');
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
		$mailBoxMapper = OC::$server->query(MailboxMapper::class);
		$dbInbox = $mailBoxMapper->find(new Account($this->account), $inbox->__toString());
		/** @var SyncService $syncService */
		$syncService = OC::$server->query(SyncService::class);
		$syncService->syncMailbox(
			new Account($this->account),
			$dbInbox,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
			[],
			false
		);
		/** @var MessageMapper $messageMapper */
		$messageMapper = OC::$server->query(MessageMapper::class);
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
		$message->setBody('message');
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
		$message->setBody('message');
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
		$message->setBody('message');
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
		$message->setBody('message');
		$message->setHtml(true);

		$to = [[
			'label' => 'Penny',
			'email' => 'library@stardewvalley.com'
		]];

		$saved = $this->outbox->saveMessage(new Account($this->account), $message, $to, [], []);
		$this->assertNotEmpty($message->getRecipients());
		$this->assertCount(1, $saved->getRecipients());
		$this->assertEmpty($message->getAttachments());

		$this->outbox->sendMessage($saved, new Account($this->account));

		$this->expectException(DoesNotExistException::class);
		$this->outbox->getMessage($message->getId(), $this->user->getUID());
	}

	public function testSaveAndFlush(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($this->account->getId());
		$message->setSubject('subject');
		$message->setBody('message');
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
