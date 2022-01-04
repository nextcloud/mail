<?php

declare(strict_types=1);

/**
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author 2022 Anna Larch <anna.larch@gmx.net>
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

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Recipient;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;

class RecipientTest extends TestCase {

	/** @var ITimeFactory|MockObject  */
	private $timeFactory;

	protected function setUp(): void {
		$this->timeFactory = $this->createMock(ITimeFactory::class);
	}

	public function testObject(): void {
		$time = $this->timeFactory->getTime();
		$recipient = new Recipient();

		$recipient->setMessageId(1);
		$recipient->setType(Recipient::TYPE_TO);
		$recipient->setMailboxType(Recipient::TYPE_OUTBOX);
		$recipient->setLabel('Penny');
		$recipient->setEmail('penny@stardew-library.edu');


		$this->assertEquals(1, $recipient->getId());
		$this->assertEquals(Recipient::TYPE_TO, $recipient->getType());
		$this->assertEquals(Recipient::TYPE_OUTBOX, $recipient->getMailboxType());
		$this->assertEquals('Penny', $recipient->getLabel());
		$this->assertEquals('penny@stardew-library.edu', $recipient->getEmail());
	}
}
