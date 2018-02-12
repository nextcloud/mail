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

namespace OCA\Mail\Tests\IMAP\Sync;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Base;
use Horde_Imap_Client_Data_Sync;
use Horde_Imap_Client_Ids;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\IMAP\Sync\FavouritesMailboxSync;
use OCA\Mail\IMAP\Sync\Request;
use OCA\Mail\Model\IMAPMessage;
use PHPUnit_Framework_MockObject_MockObject;

class FavouritesMailboxSyncTest extends TestCase {

	/** @var MessageMapper|PHPUnit_Framework_MockObject_MockObject */
	private $mapper;

	/** @var FavouritesMailboxSync */
	private $sync;

	protected function setUp() {
		parent::setUp();

		$this->mapper = $this->createMock(MessageMapper::class);

		$this->sync = new FavouritesMailboxSync($this->mapper);
	}

	public function testGetNewMessages() {
		$imapClient = $this->createMock(Horde_Imap_Client_Base::class);
		$syncRequest = $this->createMock(Request::class);
		$hordeSync = $this->createMock(Horde_Imap_Client_Data_Sync::class);
		$syncRequest->expects($this->once())
			->method('getMailbox')
			->willReturn('inbox');
		$hordeSync->newmsgsuids = $this->createMock(Horde_Imap_Client_Ids::class);
		$hordeSync->newmsgsuids->ids = [23, 24];
		$message1 = $this->createMock(IMAPMessage::class);
		$message2 = $this->createMock(IMAPMessage::class);
		$this->mapper->expects($this->once())
			->method('findByIds')
			->willReturn([
				$message1,
				$message2,
		]);
		$message1->expects($this->once())
			->method('getFlags')
			->willReturn([
				'flagged' => true,
		]);
		$message2->expects($this->once())
			->method('getFlags')
			->willReturn([
				'flagged' => false,
		]);

		$messages = $this->sync->getNewMessages($imapClient, $syncRequest, $hordeSync);

		$this->assertCount(1, $messages);
		$this->assertSame($message1, $messages[0]);
	}

}
