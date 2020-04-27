<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Tests\Unit\Service\Classification;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Service\Classification\IImportanceEstimator;

class ClassifierTest extends TestCase {
	public function testShortcut(): void {
		$c1 = $this->createMock(IImportanceEstimator::class);
		$c1->method('isImportant')->willReturn(true);
		$c2 = $this->createMock(IImportanceEstimator::class);
		$c2->expects($this->never())
			->method('isImportant');
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$message = $this->createMock(Message::class);

		$result = $c1->or($c2)->isImportant($account, $mailbox, $message);

		$this->assertTrue($result);
	}

	public function testOr(): void {
		$c1 = $this->createMock(IImportanceEstimator::class);
		$c2 = $this->createMock(IImportanceEstimator::class);
		$c2->method('isImportant')->willReturn(true);
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$message = $this->createMock(Message::class);

		$result = $c1->or($c2)->isImportant($account, $mailbox, $message);

		$this->assertTrue($result);
	}

	public function testNone(): void {
		$c1 = $this->createMock(IImportanceEstimator::class);
		$c2 = $this->createMock(IImportanceEstimator::class);
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$message = $this->createMock(Message::class);

		$result = $c1->or($c2)->isImportant($account, $mailbox, $message);

		$this->assertFalse($result);
	}
}
