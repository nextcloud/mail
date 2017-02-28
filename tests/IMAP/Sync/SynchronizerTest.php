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
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\IMAP\Sync\Synchronizer;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class SynchronizerTest extends PHPUnit_Framework_TestCase {

	/** @var MessageMapper|PHPUnit_Framework_MockObject_MockObject */
	private $messageMapper;

	/** @var Synchronizer */
	private $synchronizer;

	protected function setUp() {
		parent::setUp();

		$this->messageMapper = $this->createMock(MessageMapper::class);

		$this->synchronizer = new Synchronizer($this->messageMapper);
	}

	private function getHordeMessageIdMock(array $ids) {
		$hordeIds = $this->createMock(Horde_Imap_Client_Ids::class);
		$hordeIds->ids = $ids;
		return $hordeIds;
	}

	public function testSync() {
		$imapClient = $this->createMock(Horde_Imap_Client_Base::class);
		$request = $this->createMock(Request::class);
		$request->expects($this->any())
			->method('getMailbox')
			->willReturn('inbox');
		$request->expects($this->once())
			->method('getToken')
			->willReturn('123456');
		$hordeSync = $this->createMock(Horde_Imap_Client_Data_Sync::class);
		$imapClient->expects($this->once())
			->method('sync')
			->with($this->equalTo('inbox'), $this->equalTo('123456'))
			->willReturn($hordeSync);
		$newMessages = [];
		$changedMessages = [];
		$vanishedMessages = [4, 5];
		$hordeSync->newmsgsuids = $this->getHordeMessageIdMock($newMessages);
		$hordeSync->flagsuids = $this->getHordeMessageIdMock($changedMessages);
		$hordeSync->vanisheduids = $this->getHordeMessageIdMock($vanishedMessages);
		$this->messageMapper->expects($this->exactly(2))
			->method('findByIds')
			->with($imapClient, $this->equalTo('inbox'), $this->equalTo([]))
			->willReturn([]);
		$imapClient->expects($this->once())
			->method('getSyncToken')
			->with($this->equalTo('inbox'))
			->willReturn('54321');
		$expected = new Response('54321', $newMessages, $changedMessages, $vanishedMessages);

		$response = $this->synchronizer->sync($imapClient, $request);

		$this->assertEquals($expected, $response);
	}

}
