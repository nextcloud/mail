<?php

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

namespace OCA\Mail\Tests\Integration;

use OC;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Controller\FoldersController;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Tests\Integration\Framework\ImapTest;
use OCA\Mail\Tests\Integration\Framework\ImapTestAccount;

class FolderSynchronizationTest extends TestCase {

	use ImapTest,
	 ImapTestAccount;

	/** @var FoldersController */
	private $foldersController;

	protected function setUp() {
		parent::setUp();

		$this->foldersController = new FoldersController('mail', OC::$server->getRequest(), OC::$server->query(AccountService::class), $this->getTestAccountUserId(), OC::$server->query(IMailManager::class));
	}

	public function testSyncEmptyMailbox() {
		$account = $this->createTestAccount();
		$mailbox = 'INBOX';
		$syncToken = $this->getMailboxSyncToken($mailbox);

		$sync = $this->foldersController->sync($account->getId(), base64_encode($mailbox), $syncToken);
		$syncJson = $sync->jsonSerialize();

		$this->assertArrayHasKey('newMessages', $syncJson);
		$this->assertArrayHasKey('changedMessages', $syncJson);
		$this->assertArrayHasKey('vanishedMessages', $syncJson);
		$this->assertArrayHasKey('token', $syncJson);
		$this->assertEmpty($syncJson['newMessages']);
		$this->assertEmpty($syncJson['changedMessages']);
		$this->assertEmpty($syncJson['vanishedMessages']);
	}

	public function testSyncNewMessage() {
		// First, set up account and retrieve sync token
		$account = $this->createTestAccount();
		$mailbox = 'INBOX';
		$syncToken = $this->getMailboxSyncToken($mailbox);
		// Second, put a new message into the mailbox
		$message = $this->getMessageBuilder()
			->from('ralph@buffington@domain.tld')
			->to('user@domain.tld')
			->finish();
		$this->saveMessage($mailbox, $message);

		$sync = $this->foldersController->sync($account->getId(), base64_encode($mailbox), $syncToken);
		$syncJson = $sync->jsonSerialize();

		$this->assertCount(1, $syncJson['newMessages']);
		$this->assertCount(0, $syncJson['changedMessages']);
		$this->assertCount(0, $syncJson['vanishedMessages']);
	}

}
