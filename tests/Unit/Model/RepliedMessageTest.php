<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
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
