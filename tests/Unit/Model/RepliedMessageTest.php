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

namespace OCA\Mail\Tests\Unit\Model;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\Message;
use OCA\Mail\Model\RepliedMessageData;
use PHPUnit\Framework\MockObject\MockObject;

class RepliedMessageTest extends TestCase {
	public function testGetAccount() {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$message = new Message();
		$data = new RepliedMessageData($account, $message);

		$this->assertEquals($account, $data->getAccount());
	}

	public function testGetMessage() {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$message = new Message();
		$data = new RepliedMessageData($account, $message);

		$this->assertEquals($message, $data->getMessage());
	}
}
