<?php

declare(strict_types=1);

/**
 * @author Anna Larch <anna.larch@gmx.net>
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
use OC;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\LocalAttachmentMapper;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\Attachment\AttachmentStorage;
use OCA\Mail\Service\DraftsService;
use OCA\Mail\Service\OutboxService;
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

class DraftServiceIntegrationTest extends TestCase {
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
	private $service;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	/** @var IMAPClientFactory */
	private $clientFactory;

	/** @var LocalMessageMapper */
	private $mapper;

	/** @var Folder */
	private $userFolder;

	/**  @var AccountService|\PHPUnit\Framework\MockObject\MockObject */
	private $accountService;

	/** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
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
		$this->accountService = $this->createMock(AccountService::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->db = \OC::$server->getDatabaseConnection();
		$qb = $this->db->getQueryBuilder();
		$delete = $qb->delete($this->mapper->getTableName());
		$delete->execute();

		$this->service = new DraftsService(
			$this->transmission,
			$this->mapper,
			$this->attachmentService,
			$this->eventDispatcher,
			$this->clientFactory,
			$mailManager,
			$this->createMock(LoggerInterface::class),
			$this->accountService,
			$this->timeFactory
		);
	}

	public function testSaveAndGetMessage(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_DRAFT);
		$message->setAccountId($this->account->getId());
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setHtml(true);

		$to = [[
			'label' => 'Penny',
			'email' => 'library@stardewvalley.com'
		]];

		$saved = $this->service->saveMessage(new Account($this->account), $message, $to, [], []);
		$this->assertNotEmpty($message->getRecipients());
		$this->assertEmpty($message->getAttachments());

		$retrieved = $this->service->getMessage($message->getId(), $this->user->getUID());
		$this->assertNotEmpty($message->getRecipients());
		$this->assertEmpty($message->getAttachments());

		self::assertCount(1, $retrieved->getRecipients());
	}

	public function testSaveAndDeleteMessage(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_DRAFT);
		$message->setAccountId($this->account->getId());
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setHtml(true);

		$to = [[
			'label' => 'Penny',
			'email' => 'library@stardewvalley.com'
		]];

		$saved = $this->service->saveMessage(new Account($this->account), $message, $to, [], []);
		$this->assertNotEmpty($message->getRecipients());
		$this->assertEmpty($message->getAttachments());

		$this->service->deleteMessage($this->user->getUID(), $saved);

		$this->expectException(DoesNotExistException::class);
		$this->service->getMessage($message->getId(), $this->user->getUID());
	}

	public function testSaveAndUpdateMessage(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_DRAFT);
		$message->setAccountId($this->account->getId());
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setHtml(true);

		$to = [[
			'label' => 'Penny',
			'email' => 'library@stardewvalley.com'
		]];

		$saved = $this->service->saveMessage(new Account($this->account), $message, $to, [], []);
		$this->assertNotEmpty($message->getRecipients());
		$this->assertCount(1, $saved->getRecipients());
		$this->assertEmpty($message->getAttachments());

		$saved->setSubject('Your Trailer will be put up for sale');
		$cc = [[
			'label' => 'Pam',
			'email' => 'buyMeABeer@stardewvalley.com'
		]];
		$updated = $this->service->updateMessage(new Account($this->account), $saved, $to, $cc, []);

		$this->assertNotEmpty($updated->getRecipients());
		$this->assertEquals('Your Trailer will be put up for sale', $updated->getSubject());
		$this->assertCount(2, $updated->getRecipients());
	}
	public function testSaveAndConvertToOutboxMessage(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_DRAFT);
		$message->setAccountId($this->account->getId());
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setHtml(true);

		$to = [[
			'label' => 'Penny',
			'email' => 'library@stardewvalley.com'
		]];

		$saved = $this->service->saveMessage(new Account($this->account), $message, $to, [], []);
		$this->assertNotEmpty($message->getRecipients());
		$this->assertCount(1, $saved->getRecipients());
		$this->assertEmpty($message->getAttachments());

		$saved->setSubject('Your Trailer will be put up for sale');
		$cc = [[
			'label' => 'Pam',
			'email' => 'buyMeABeer@stardewvalley.com'
		]];
		$saved->setType(LocalMessage::TYPE_OUTGOING);
		$saved->setSendAt(123456);
		$updated = $this->service->updateMessage(new Account($this->account), $saved, $to, $cc, []);

		$this->assertNotEmpty($updated->getRecipients());
		$this->assertEquals('Your Trailer will be put up for sale', $updated->getSubject());
		$this->assertCount(2, $updated->getRecipients());
		$this->assertEquals(LocalMessage::TYPE_OUTGOING, $saved->getType());
	}


	public function testSaveAndSendMessage(): void {
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_DRAFT);
		$message->setAccountId($this->account->getId());
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setHtml(true);

		$to = [[
			'label' => 'Penny',
			'email' => 'library@stardewvalley.com'
		]];

		$saved = $this->service->saveMessage(new Account($this->account), $message, $to, [], []);
		$this->assertNotEmpty($message->getRecipients());
		$this->assertCount(1, $saved->getRecipients());
		$this->assertEmpty($message->getAttachments());

		$this->service->sendMessage($saved, new Account($this->account));

		$this->expectException(DoesNotExistException::class);
		$this->service->getMessage($message->getId(), $this->user->getUID());
	}
}
