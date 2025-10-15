<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\Service\AntiSpamService;
use OCA\Mail\Service\Sync\SyncService;
use OCA\Mail\Tests\Integration\Framework\ImapTest;
use OCA\Mail\Tests\Integration\Framework\ImapTestAccount;
use OCP\Server;

class AntiSpamServiceIntegrationTest extends TestCase {
	use ImapTest,
		ImapTestAccount;

	/** @var AntiSpamService */
	private $service;

	public function setUp():void {
		parent::setUp();
		$this->service = Server::get(AntiSpamService::class);
		$this->service->setSpamEmail('spam@domain.tld');
		$this->service->setHamEmail('notspam@domain.tld');
	}

	public function tearDown(): void {
		$this->resetImapAccount();
		$this->disconnectImapAccount();
		$this->service->deleteConfig();
	}

	public function testFlagJunkWithSpamReportActive(): void {
		// First, set up account and retrieve sync token
		$this->resetImapAccount();
		$this->disconnectImapAccount();
		$account = $this->createTestAccount();

		/** @var SyncService $syncService */
		$syncService = Server::get(SyncService::class);
		/** @var ImapMessageMapper $imapMessageMapper */
		$imapMessageMapper = Server::get(ImapMessageMapper::class);
		/** @var MessageMapper $messageMapper */
		$messageMapper = Server::get(MessageMapper::class);
		/** @var IMailManager $mailManager */
		$mailManager = Server::get(IMailManager::class);
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

		// sync in between creating and flagging otherwise it can't be found
		$syncService->syncMailbox(
			new Account($account),
			$inbox,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
			false,
			null,
			null
		);

		// now we flag this message as junk
		$mailManager->flagMessage(new Account($account), $inbox->getName(), $newUid, 'junk', true);

		// if everything runs through, we can assert the run has been fine,
		// but we can't really test if Listener and Transmission have actually sent the message
		$this->addToAssertionCount(1);

		// now we flag this message as not junk
		$mailManager->flagMessage(new Account($account), $inbox->getName(), $newUid, 'notjunk', true);

		// same as before
		$this->addToAssertionCount(1);
	}
}
