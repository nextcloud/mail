<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Service\Search\SearchQuery;
use OCA\Mail\Support\PerformanceLogger;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;
use function array_map;
use function range;
use function time;

class MessageMapperTest extends TestCase {
	use DatabaseTransaction;

	/** A user that has no mail accounts on a development instance */
	private const GLOBAL_SEARCH_USER_ID = 'message-mapper-test-user';

	/** @var IDBConnection */
	private $db;

	/** @var ITimeFactory */
	private $time;

	private int $timestamp = 1234567890;

	/** @var MessageMapper */
	private $mapper;

	protected function setUp(): void {
		parent::setUp();

		$this->db = \OCP\Server::get(\OCP\IDBConnection::class);
		$this->time = $this->createMock(ITimeFactory::class);
		$this->time->method('getTime')->willReturnCallback(fn () => $this->timestamp);
		$tagMapper = $this->createMock(TagMapper::class);
		$performanceLogger = $this->createMock(PerformanceLogger::class);
		$this->mapper = new MessageMapper(
			$this->db,
			$this->time,
			$tagMapper,
			$performanceLogger
		);

		$qb = $this->db->getQueryBuilder();

		$delete = $qb->delete($this->mapper->getTableName());
		$delete->executeStatement();
	}

	private function insertMessage(int $uid, int $mailbox_id): int {
		$qb = $this->db->getQueryBuilder();
		$insert = $qb->insert($this->mapper->getTableName())
			->values([
				'uid' => $qb->createNamedParameter($uid, IQueryBuilder::PARAM_INT),
				'message_id' => $qb->createNamedParameter('<abc' . $uid . $mailbox_id . '@123.com>'),
				'mailbox_id' => $qb->createNamedParameter($mailbox_id, IQueryBuilder::PARAM_INT),
				'subject' => $qb->createNamedParameter('TEST'),
				'sent_at' => $qb->createNamedParameter($this->time->getTime(), IQueryBuilder::PARAM_INT),
				'in_reply_to' => $qb->createNamedParameter('<>')
			]);
		$insert->executeStatement();
		return $qb->getLastInsertId();
	}

	private function insertMessageWithId(int $id, int $mailbox_id): void {
		$qb = $this->db->getQueryBuilder();
		$insert = $qb->insert($this->mapper->getTableName())
			->values([
				'id' => $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT),
				'uid' => $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT),
				'message_id' => $qb->createNamedParameter('<abc' . $id . $mailbox_id . '@123.com>'),
				'mailbox_id' => $qb->createNamedParameter($mailbox_id, IQueryBuilder::PARAM_INT),
				'subject' => $qb->createNamedParameter('TEST'),
				'sent_at' => $qb->createNamedParameter($this->time->getTime(), IQueryBuilder::PARAM_INT),
				'in_reply_to' => $qb->createNamedParameter('<>')
			]);
		$insert->executeStatement();
	}

	private function insertRecipient(int $messageId, int $type, string $email, string $label = ''): void {
		$qb = $this->db->getQueryBuilder();
		$qb->insert('mail_recipients')
			->values([
				'message_id' => $qb->createNamedParameter($messageId, IQueryBuilder::PARAM_INT),
				'type' => $qb->createNamedParameter($type, IQueryBuilder::PARAM_INT),
				'email' => $qb->createNamedParameter($email),
				'label' => $qb->createNamedParameter($label),
			])
			->executeStatement();
	}

	private function insertMailboxOfUser(string $userId): Mailbox {
		$account = new MailAccount();
		$account->setUserId($userId);
		$account->setEmail($userId . '@example.com');
		$account->setInboundHost('imap.example.com');
		$account->setInboundPort(993);
		$account->setInboundSslMode('ssl');
		$account->setInboundUser($userId);
		$account = (new MailAccountMapper($this->db))->insert($account);

		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$mailbox->setNameHash(md5('INBOX'));
		$mailbox->setAccountId($account->getId());
		$mailbox->setAttributes('[]');
		$mailbox->setDelimiter('.');
		$mailbox->setMessages(0);
		$mailbox->setUnseen(0);
		$mailbox->setSelectable(true);
		$mailbox->setSpecialUse('[]');
		return (new MailboxMapper($this->db, $this->time))->insert($mailbox);
	}

	private function insertTag(string $imapMessageId, int $tagId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->insert('mail_message_tags')
			->values([
				'imap_message_id' => $qb->createNamedParameter($imapMessageId),
				'tag_id' => $qb->createNamedParameter($tagId, IQueryBuilder::PARAM_INT),
			])
			->executeStatement();
	}

	public function testFindIdsByQueryMatchesTheSender(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$this->insertMessageWithId(1, 1);
		$this->insertMessageWithId(2, 1);
		$this->insertRecipient(1, Recipient::TYPE_FROM, 'alice@example.com');
		$this->insertRecipient(2, Recipient::TYPE_FROM, 'bob@example.com');

		$searchQuery = new SearchQuery();
		$searchQuery->addFrom('alice@example.com');

		$result = $this->mapper->findIdsByQuery($mailbox, $searchQuery, 'DESC', null);

		self::assertEquals([1], $result);
	}

	public function testFindIdsByQueryMatchesTheRecipientLabel(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$this->insertMessageWithId(1, 1);
		$this->insertRecipient(1, Recipient::TYPE_TO, 'alice@example.com', 'Alice Doe');

		$searchQuery = new SearchQuery();
		$searchQuery->addTo('Alice');

		$result = $this->mapper->findIdsByQuery($mailbox, $searchQuery, 'DESC', null);

		self::assertEquals([1], $result);
	}

	public function testFindIdsByQueryMatchesCcAndBcc(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$this->insertMessageWithId(1, 1);
		$this->insertMessageWithId(2, 1);
		$this->insertRecipient(1, Recipient::TYPE_CC, 'cc@example.com');
		$this->insertRecipient(1, Recipient::TYPE_BCC, 'bcc@example.com');
		$this->insertRecipient(2, Recipient::TYPE_CC, 'other@example.com');

		$searchQuery = new SearchQuery();
		$searchQuery->addCc('cc@example.com');
		$searchQuery->addBcc('bcc@example.com');

		$result = $this->mapper->findIdsByQuery($mailbox, $searchQuery, 'DESC', null);

		self::assertEquals([1], $result);
	}

	public function testFindIdsByQueryDoesNotDuplicateAMessageWithSeveralMatchingRecipients(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$this->insertMessageWithId(1, 1);
		// Two "to" recipients both match the search, which used to multiply
		// the message once per join before DISTINCT removed the duplicate.
		$this->insertRecipient(1, Recipient::TYPE_TO, 'alice@example.com');
		$this->insertRecipient(1, Recipient::TYPE_TO, 'alice@work.example.com');

		$searchQuery = new SearchQuery();
		$searchQuery->addTo('alice@');

		$result = $this->mapper->findIdsByQuery($mailbox, $searchQuery, 'DESC', null);

		self::assertEquals([1], $result);
	}

	public function testFindIdsByQueryDoesNotDuplicateAMessageWithSeveralMatchingTags(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$this->insertMessageWithId(1, 1);
		// Same concern as the recipient case: a message with several of the
		// requested tags must not come back once per tag now that the join
		// is a subquery and DISTINCT is gone.
		$this->insertTag('<abc11@123.com>', 101);
		$this->insertTag('<abc11@123.com>', 102);

		$searchQuery = new SearchQuery();
		$searchQuery->setTags([101, 102]);

		$result = $this->mapper->findIdsByQuery($mailbox, $searchQuery, 'DESC', null);

		self::assertEquals([1], $result);
	}

	public function testFindIdsByQueryAllofRequiresBothSenderAndSubject(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$qb = $this->db->getQueryBuilder();
		$qb->insert($this->mapper->getTableName())->values([
			'id' => 1,
			'uid' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
			'message_id' => $qb->createNamedParameter('<a@b.com>'),
			'mailbox_id' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
			'subject' => $qb->createNamedParameter('needle'),
			'sent_at' => $qb->createNamedParameter(1641216000, IQueryBuilder::PARAM_INT),
		])->executeStatement();
		$this->insertRecipient(1, Recipient::TYPE_FROM, 'alice@example.com');

		$searchQuery = new SearchQuery();
		$searchQuery->addFrom('alice@example.com');
		$searchQuery->addSubject('nonexistent');

		$result = $this->mapper->findIdsByQuery($mailbox, $searchQuery, 'DESC', null);

		self::assertEquals([], $result);
	}

	public function testFindIdsByQueryAnyofMatchesEitherSenderOrSubject(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$qb = $this->db->getQueryBuilder();
		$qb->insert($this->mapper->getTableName())->values([
			'id' => 1,
			'uid' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
			'message_id' => $qb->createNamedParameter('<a@b.com>'),
			'mailbox_id' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
			'subject' => $qb->createNamedParameter('unrelated'),
			'sent_at' => $qb->createNamedParameter(1641216000, IQueryBuilder::PARAM_INT),
		])->executeStatement();
		$this->insertRecipient(1, Recipient::TYPE_FROM, 'alice@example.com');

		$searchQuery = new SearchQuery();
		$searchQuery->setMatch('anyof');
		$searchQuery->addFrom('alice@example.com');
		$searchQuery->addSubject('needle');

		$result = $this->mapper->findIdsByQuery($mailbox, $searchQuery, 'DESC', null);

		self::assertEquals([1], $result);
	}

	public function testFindIdsGloballyByQueryMatchesTheSender(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn(self::GLOBAL_SEARCH_USER_ID);
		$mailbox = $this->insertMailboxOfUser(self::GLOBAL_SEARCH_USER_ID);
		$fromAlice = $this->insertMessage(1, $mailbox->getId());
		$fromBob = $this->insertMessage(2, $mailbox->getId());
		$this->insertRecipient($fromAlice, Recipient::TYPE_FROM, 'alice@example.com');
		$this->insertRecipient($fromBob, Recipient::TYPE_FROM, 'bob@example.com');

		$searchQuery = new SearchQuery();
		$searchQuery->addFrom('alice@example.com');

		$result = $this->mapper->findIdsGloballyByQuery($user, $searchQuery, null);

		self::assertEquals([$fromAlice], $result);
	}

	public function testFindIdsGloballyByQueryMatchesToCcAndBcc(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn(self::GLOBAL_SEARCH_USER_ID);
		$mailbox = $this->insertMailboxOfUser(self::GLOBAL_SEARCH_USER_ID);
		$matching = $this->insertMessage(1, $mailbox->getId());
		$onlyTo = $this->insertMessage(2, $mailbox->getId());
		$this->insertRecipient($matching, Recipient::TYPE_TO, 'to@example.com');
		$this->insertRecipient($matching, Recipient::TYPE_CC, 'cc@example.com');
		$this->insertRecipient($matching, Recipient::TYPE_BCC, 'bcc@example.com');
		$this->insertRecipient($onlyTo, Recipient::TYPE_TO, 'to@example.com');

		$searchQuery = new SearchQuery();
		$searchQuery->addTo('to@example.com');
		$searchQuery->addCc('cc@example.com');
		$searchQuery->addBcc('bcc@example.com');

		$result = $this->mapper->findIdsGloballyByQuery($user, $searchQuery, null);

		self::assertEquals([$matching], $result);
	}

	public function testResetInReplyTo() : void {
		$account = $this->createMock(Account::class);
		$account->method('getId')->willReturn(13);
		array_map(function ($i) {
			$qb = $this->db->getQueryBuilder();
			$insert = $qb->insert($this->mapper->getTableName())
				->values([
					'uid' => $qb->createNamedParameter($i, IQueryBuilder::PARAM_INT),
					'message_id' => $qb->createNamedParameter('<abc' . $i . '@123.com>'),
					'mailbox_id' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
					'subject' => $qb->createNamedParameter('TEST'),
					'sent_at' => $qb->createNamedParameter($this->time->getTime(), IQueryBuilder::PARAM_INT),
					'in_reply_to' => $qb->createNamedParameter('<>')
				]);
			$insert->executeStatement();
		}, range(1, 10));

		array_map(function ($i) {
			$qb = $this->db->getQueryBuilder();
			$insert = $qb->insert($this->mapper->getTableName())
				->values([
					'uid' => $qb->createNamedParameter($i, IQueryBuilder::PARAM_INT),
					'message_id' => $qb->createNamedParameter('<abc' . $i . '@123.com>'),
					'mailbox_id' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
					'subject' => $qb->createNamedParameter('TEST'),
					'sent_at' => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
					'in_reply_to' => $qb->createNamedParameter('<abc@dgf.com>')
				]);
			$insert->executeStatement();
		}, range(11, 20));

		$result = $this->mapper->resetInReplyTo();

		$this->assertEquals(10, $result);

		$qb2 = $this->db->getQueryBuilder();
		$select = $qb2->select('*')
			->from($this->mapper->getTableName())
			->where(
				$qb2->expr()->like('in_reply_to', $qb2->createNamedParameter('<>', IQueryBuilder::PARAM_STR), IQueryBuilder::PARAM_STR)
			);

		$result = $select->executeQuery();
		$rows = $result->fetchAll();

		$this->assertEmpty($rows);
	}

	public function testResetPreviewDataFlag(): void {
		$uid = time();
		$qb = $this->db->getQueryBuilder();
		$insert = $qb->insert($this->mapper->getTableName())
			->values([
				'uid' => $qb->createNamedParameter($uid, IQueryBuilder::PARAM_INT),
				'message_id' => $qb->createNamedParameter('<abc@123.com>'),
				'mailbox_id' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
				'subject' => $qb->createNamedParameter('TEST'),
				'sent_at' => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
			]);
		$insert->executeStatement();

		$this->mapper->resetPreviewDataFlag();

		$qb2 = $this->db->getQueryBuilder();
		$result = $qb2->select($qb2->func()->count('*'))
			->from($this->mapper->getTableName())
			->where(
				$qb2->expr()->eq('uid', $qb2->createNamedParameter($uid, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT),
				$qb2->expr()->eq('structure_analyzed', $qb2->createNamedParameter(true, IQueryBuilder::PARAM_BOOL), IQueryBuilder::PARAM_BOOL)
			)
			->executeQuery();
		$cnt = $result->fetchOne();
		$result->closeCursor();
		self::assertEquals(0, $cnt);
	}

	public function testFindIdsByQuery(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$searchQuery = new SearchQuery();
		$sortOrder = 'DESC';
		$qb = $this->db->getQueryBuilder();

		$values = [
			[
				'id' => 1,
				'uid' => $qb->createNamedParameter(267, IQueryBuilder::PARAM_INT),
				'message_id' => $qb->createNamedParameter('<abc@123.com>'),
				'mailbox_id' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
				'subject' => $qb->createNamedParameter('TEST 1'),
				'sent_at' => $qb->createNamedParameter(1641216000, IQueryBuilder::PARAM_INT),
			],
			[
				'id' => 2,
				'uid' => $qb->createNamedParameter(268, IQueryBuilder::PARAM_INT),
				'message_id' => $qb->createNamedParameter('<def@456.com>'),
				'mailbox_id' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
				'subject' => $qb->createNamedParameter('TEST 2'),
				'sent_at' => $qb->createNamedParameter(1641216001, IQueryBuilder::PARAM_INT),
			],
			[
				'id' => 3,
				'uid' => $qb->createNamedParameter(269, IQueryBuilder::PARAM_INT),
				'message_id' => $qb->createNamedParameter('<ghi@789.com>'),
				'mailbox_id' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
				'subject' => $qb->createNamedParameter('TEST 3'),
				'sent_at' => $qb->createNamedParameter(1641216003, IQueryBuilder::PARAM_INT),
			],
		];

		foreach ($values as $value) {
			$insert = $qb->insert($this->mapper->getTableName())->values($value);
			$insert->executeStatement();
		}

		$result = $this->mapper->findIdsByQuery($mailbox, $searchQuery, $sortOrder, 3, null);

		self::assertEquals([3,2,1], $result);
	}

	public function testFindIdsByQueryRestrictedToIds(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$qb = $this->db->getQueryBuilder();

		$values = [
			[
				'id' => 1,
				'uid' => $qb->createNamedParameter(267, IQueryBuilder::PARAM_INT),
				'message_id' => $qb->createNamedParameter('<abc@123.com>'),
				'mailbox_id' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
				'subject' => $qb->createNamedParameter('needle'),
				'sent_at' => $qb->createNamedParameter(1641216000, IQueryBuilder::PARAM_INT),
			],
			[
				'id' => 2,
				'uid' => $qb->createNamedParameter(268, IQueryBuilder::PARAM_INT),
				'message_id' => $qb->createNamedParameter('<def@456.com>'),
				'mailbox_id' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
				'subject' => $qb->createNamedParameter('needle'),
				'sent_at' => $qb->createNamedParameter(1641216001, IQueryBuilder::PARAM_INT),
			],
			[
				'id' => 3,
				'uid' => $qb->createNamedParameter(269, IQueryBuilder::PARAM_INT),
				'message_id' => $qb->createNamedParameter('<ghi@789.com>'),
				'mailbox_id' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
				'subject' => $qb->createNamedParameter('unrelated'),
				'sent_at' => $qb->createNamedParameter(1641216003, IQueryBuilder::PARAM_INT),
			],
		];

		foreach ($values as $value) {
			$qb->insert($this->mapper->getTableName())->values($value)->executeStatement();
		}

		$searchQuery = new SearchQuery();
		$searchQuery->setMatch('anyof');
		$searchQuery->addSubject('needle');

		$result = $this->mapper->findIdsByQuery($mailbox, $searchQuery, 'DESC', null, null, [2, 3]);

		self::assertEquals([2], $result);
	}

	public function testFindIdsByQueryRestrictedToNoIds(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$this->insertMessage(267, 1);

		$searchQuery = new SearchQuery();
		$searchQuery->setMatch('anyof');
		$searchQuery->addSubject('TEST');

		$result = $this->mapper->findIdsByQuery($mailbox, $searchQuery, 'DESC', null, null, []);

		self::assertEquals([], $result);
	}

	public function testDeleteByUid(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		array_map(function ($i) {
			$this->insertMessage($i, 1);
		}, range(1, 10));

		$this->mapper->deleteByUid($mailbox, 1, 5);

		$messages = $this->mapper->findByUids($mailbox, range(1, 10));
		self::assertCount(8, $messages);
	}

	public function testDeleteDuplicateUids(): void {
		$mailbox1 = new Mailbox();
		$mailbox1->setId(1);
		$mailbox2 = new Mailbox();
		$mailbox2->setId(2);
		$mailbox3 = new Mailbox();
		$mailbox3->setId(3);
		$this->insertMessage(100, 1);
		$this->insertMessage(101, 1);
		$this->insertMessage(101, 1);
		$this->insertMessage(102, 1);
		$this->insertMessage(102, 1);
		$this->insertMessage(102, 1);
		$this->insertMessage(103, 2);
		$this->insertMessage(104, 2);
		$this->insertMessage(104, 2);
		$this->insertMessage(105, 3);

		$this->mapper->deleteDuplicateUids();

		self::assertCount(1, $this->mapper->findByUids($mailbox1, [100]));
		self::assertCount(1, $this->mapper->findByUids($mailbox1, [101]));
		self::assertCount(1, $this->mapper->findByUids($mailbox1, [102]));
		self::assertCount(1, $this->mapper->findByUids($mailbox2, [103]));
		self::assertCount(1, $this->mapper->findByUids($mailbox2, [104]));
		self::assertCount(1, $this->mapper->findByUids($mailbox3, [105]));
	}

	public function testFindIdsAfter() : void {
		$mailbox = new Mailbox();
		$mailbox->setId(4);
		$this->timestamp = 1234567890;
		array_map(function ($i) use ($mailbox) {
			$this->insertMessageWithId($i, $mailbox->getId());
		}, range(1, 5));
		$this->timestamp = 1234567891 + 100;
		array_map(function ($i) use ($mailbox) {
			$this->insertMessageWithId($i, $mailbox->getId());
		}, range(6, 10));

		$mails = $this->mapper->findIdsAfter($mailbox, 2, 0, 5);
		$this->assertEquals([3,4,5,6,7], $mails);

		$mails = $this->mapper->findIdsAfter($mailbox, 2, 1234567890, 5);
		$this->assertEquals([6,7,8,9,10], $mails);

		$mails = $this->mapper->findIdsAfter($mailbox, 2, 1234567890 + 200, 5);
		$this->assertEquals([], $mails);
	}

	public function testInsertBulkConvertsNonUtf8Headers(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('user');
		$message = new Message();
		$message->setUid(1);
		$message->setMessageId('<non-utf8@example.com>');
		$message->setMailboxId(1);
		// Raw 8-bit Windows-1252 bytes without a declared charset
		$message->setSubject("Caf\xe9 \x801200");
		$message->setSentAt(1);
		foreach (['Answered', 'Deleted', 'Draft', 'Flagged', 'Seen', 'Forwarded', 'Junk', 'Notjunk', 'Important', 'Mdnsent'] as $flag) {
			$message->{'setFlag' . $flag}(false);
		}
		$message->setFrom(new AddressList([Address::fromRaw("No\xebl", 'noel@example.com')]));

		$this->mapper->insertBulk(new Account($mailAccount), $message);

		$qb = $this->db->getQueryBuilder();
		$result = $qb->select('m.subject', 'r.label')
			->from($this->mapper->getTableName(), 'm')
			->join('m', 'mail_recipients', 'r', $qb->expr()->eq('r.message_id', 'm.id'))
			->where($qb->expr()->eq('m.message_id', $qb->createNamedParameter('<non-utf8@example.com>')))
			->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		$this->assertSame('Café €1200', $row['subject']);
		$this->assertSame('Noël', $row['label']);
	}
}
