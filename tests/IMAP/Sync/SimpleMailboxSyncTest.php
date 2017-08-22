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

use Horde_Imap_Client_Base;
use Horde_Imap_Client_Data_Sync;
use Horde_Imap_Client_Ids;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\IMAP\Sync\Request;
use OCA\Mail\IMAP\Sync\SimpleMailboxSync;
use OCA\Mail\Model\IMAPMessage;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class SimpleMailboxSyncTest extends PHPUnit_Framework_TestCase {

	/** @var MessageMapper|PHPUnit_Framework_MockObject_MockObject */
	private $mapper;

	/** @var Horde_Imap_Client_Base|PHPUnit_Framework_MockObject_MockObject */
	private $imapClient;

	/** @var Request|PHPUnit_Framework_MockObject_MockObject */
	private $syncRequest;

	/** @var Horde_Imap_Client_Data_Sync|PHPUnit_Framework_MockObject_MockObject */
	private $hordeSync;

	/** @var SimpleMailboxSync */
	private $sync;

	protected function setUp() {
		parent::setUp();

		$this->mapper = $this->createMock(MessageMapper::class);
		$this->imapClient = $this->createMock(Horde_Imap_Client_Base::class);
		$this->syncRequest = $this->createMock(Request::class);
		$this->hordeSync = $this->createMock(Horde_Imap_Client_Data_Sync::class);

		$this->sync = new SimpleMailboxSync($this->mapper);
	}

	public function testGetNewMessages() {
		$this->syncRequest->expects($this->once())
			->method('getMailbox')
			->willReturn('inbox');
		$this->hordeSync->newmsgsuids = $this->createMock(Horde_Imap_Client_Ids::class);
		$this->hordeSync->newmsgsuids->ids = [23, 24];
		$this->mapper->expects($this->once())
			->method('findByIds')
			->with($this->equalTo($this->imapClient), $this->equalTo('inbox'), $this->equalTo([23, 24]))
			->willReturn([
				$this->createMock(IMAPMessage::class),
				$this->createMock(IMAPMessage::class),
		]);

		$messages = $this->sync->getNewMessages($this->imapClient, $this->syncRequest, $this->hordeSync);

		$this->assertCount(2, $messages);
	}

	public function testGetChangedMessages() {
		$this->syncRequest->expects($this->once())
			->method('getMailbox')
			->willReturn('inbox');
		$this->hordeSync->flagsuids = $this->createMock(Horde_Imap_Client_Ids::class);
		$this->hordeSync->flagsuids->ids = [23, 24];
		$this->mapper->expects($this->once())
			->method('findByIds')
			->with($this->equalTo($this->imapClient), $this->equalTo('inbox'), $this->equalTo([23, 24]))
			->willReturn([
				$this->createMock(IMAPMessage::class),
				$this->createMock(IMAPMessage::class),
		]);

		$messages = $this->sync->getChangedMessages($this->imapClient, $this->syncRequest, $this->hordeSync);

		$this->assertCount(2, $messages);
	}

	public function testGetVanishedMessages() {
		$this->hordeSync->vanisheduids = $this->createMock(Horde_Imap_Client_Ids::class);
		$this->hordeSync->vanisheduids->ids = [23, 24];

		$ids = $this->sync->getVanishedMessages($this->imapClient, $this->syncRequest, $this->hordeSync);

		$this->assertEquals([23, 24], $ids);
	}

}
