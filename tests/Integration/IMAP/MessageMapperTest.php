<?php

declare(strict_types=1);

/**
 * @author Anna Larch <anna.larch@nextcloud.com>
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

namespace OCA\Mail\Tests\Integration\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client;
use Horde_Imap_Client_Exception;
use OC;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Service\Sync\SyncService;
use OCA\Mail\Tests\Integration\Framework\ImapTest;
use OCA\Mail\Tests\Integration\Framework\ImapTestAccount;

class MessageMapperTest extends TestCase {
	use ImapTest,
		ImapTestAccount;

	public function setUp():void {
		parent::setUp();
	}

	public function tearDown(): void {
		$this->resetImapAccount();
		$this->disconnectImapAccount();
	}

	public function testTagging(): void {
		// First, set up account and retrieve sync token
		$this->resetImapAccount();
		$account = $this->createTestAccount();
		/** @var SyncService $syncService */
		$syncService = OC::$server->get(SyncService::class);
		/** @var ImapMessageMapper $imapMessageMapper */
		$imapMessageMapper = OC::$server->get(ImapMessageMapper::class);
		/** @var MessageMapper $messageMapper */
		$messageMapper = OC::$server->get(MessageMapper::class);
		/** @var IMailManager $mailManager */
		$mailManager = OC::$server->get(IMailManager::class);
		$mailBoxes = $mailManager->getMailboxes(new Account($account));
		$inbox = null;
		foreach ($mailBoxes as $mailBox) {
			if ($mailBox->getName() === 'INBOX') {
				$inbox = $mailBox;
				break;
			}
		}

		// Second, put a new message into the mailbox
		$message = $this->getMessageBuilder()
			->from('buffington@domain.tld')
			->to('user@domain.tld')
			->finish();
		$newUid = $this->saveMessage($inbox->getName(), $message, $account);

		// now we tag this message!
		$client = $this->getClient($account);
		try {
			$imapMessageMapper->addFlag($client, $inbox, [$newUid], '$label1');
		} catch (Horde_Imap_Client_Exception $e) {
			self::fail('Could not tag message');
		} finally {
			$client->logout();
		}

		// sync
		$syncService->syncMailbox(
			new Account($account),
			$inbox,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
			null,
			false
		);

		// Let's retrieve the DB to see if we have this tag!
		$messages = $messageMapper->findByUids($inbox, [$newUid]);
		$related = $messageMapper->findRelatedData($messages, $account->getUserId());
		foreach ($related as $message) {
			$tags = $message->getTags();
			self::assertCount(1, $tags);
			self::assertEquals('$label1', $tags[0]->getImapLabel());
		}


		// now we untag this message!
		$client = $this->getClient($account);
		try {
			$imapMessageMapper->removeFlag($client, $inbox, [$newUid], '$label1');
		} catch (Horde_Imap_Client_Exception $e) {
			self::fail('Could not untag message');
		} finally {
			$client->logout();
		}

		// sync again
		$syncService->syncMailbox(
			new Account($account),
			$inbox,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
			null,
			true
		);

		$messages = $messageMapper->findByUids($inbox, [$newUid]);
		$related = $messageMapper->findRelatedData($messages, $account->getUserId());
		foreach ($related as $message) {
			$tags = $message->getTags();
			self::assertEmpty($tags);
		}
	}

	public function testGetFlagged(): void {
		// First, set up account and retrieve sync token
		$this->resetImapAccount();

		$account = $this->createTestAccount();
		/** @var ImapMessageMapper $messageMapper */
		$imapMessageMapper = OC::$server->get(ImapMessageMapper::class);
		/** @var IMailManager $mailManager */
		$mailManager = OC::$server->get(IMailManager::class);
		$mailBoxes = $mailManager->getMailboxes(new Account($account));
		$inbox = null;
		foreach ($mailBoxes as $mailBox) {
			if ($mailBox->getName() === 'INBOX') {
				$inbox = $mailBox;
				break;
			}
		}

		// Put a second new message into the mailbox
		$message = $this->getMessageBuilder()
			->from('buffington@domain.tld')
			->to('user@domain.tld')
			->finish();
		$newUid = $this->saveMessage($inbox->getName(), $message, $account);

		// Put another new message into the mailbox
		$message = $this->getMessageBuilder()
			->from('fluffington@domain.tld')
			->to('user@domain.tld')
			->finish();
		$newUid2 = $this->saveMessage($inbox->getName(), $message, $account);

		// Thirdly, create a message that will not be tagged
		$message = $this->getMessageBuilder()
			->from('scruffington@domain.tld')
			->to('user@domain.tld')
			->finish();
		$this->saveMessage($inbox->getName(), $message, $account);

		// now we tag this message with $label1
		$client = $this->getClient($account);
		try {
			// now we tag this message with $label1
			$imapMessageMapper->addFlag($client, $inbox, [$newUid], '$label1');
			// now we tag this and the previous message with $label2
			$imapMessageMapper->addFlag($client, $inbox, [$newUid, $newUid2], '$label2');

			// test for labels
			$tagged = $imapMessageMapper->getFlagged($client, $inbox, '$label1');
			self::assertNotEmpty($tagged);
			// are the counts correct?
			self::assertCount(1, $tagged);

			$tagged = $imapMessageMapper->getFlagged($client, $inbox, '$label2');
			self::assertNotEmpty($tagged);
			self::assertCount(2, $tagged);

			// test for labels that wasn't set
			$tagged = $imapMessageMapper->getFlagged($client, $inbox, '$notAvailable');
			self::assertEmpty($tagged);

			// test for regular flag - recent
			$tagged = $imapMessageMapper->getFlagged($client, $inbox, Horde_Imap_Client::FLAG_RECENT);
			self::assertNotEmpty($tagged);
			// should return all messages
			self::assertCount(3, $tagged);
		} finally {
			$client->logout();
		}
	}
}
