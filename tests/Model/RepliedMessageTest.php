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

namespace OCA\Mail\Tests\Model;

use OCA\Mail\Account;
use OCA\Mail\Model\RepliedMessageData;
use PHPUnit_Framework_TestCase;

class RepliedMessageTest extends PHPUnit_Framework_TestCase {

	public function testGetAccount() {
		$account = $this->createMock(Account::class);
		$data = new RepliedMessageData($account, null, null);

		$this->assertEquals($account, $data->getAccount());
	}

	public function testGetFolderId() {
		$account = $this->createMock(Account::class);
		$folderId = base64_encode('INBOX');
		$data = new RepliedMessageData($account, $folderId, null);

		$this->assertEquals($folderId, $data->getFolderId());
	}

	public function testGetId() {
		$account = $this->createMock(Account::class);
		$messageId = 12;
		$data = new RepliedMessageData($account, null, $messageId);

		$this->assertEquals($messageId, $data->getId());
	}

	public function testIsReply() {
		$account = $this->createMock(Account::class);
		$folderId = base64_encode('INBOX');
		$messageId = 12;
		$data = new RepliedMessageData($account, $folderId, $messageId);

		$this->assertTrue($data->isReply());
	}

	public function testIsNoReply() {
		$account = $this->createMock(Account::class);
		$data = new RepliedMessageData($account, null, null);

		$this->assertFalse($data->isReply());
	}

}
