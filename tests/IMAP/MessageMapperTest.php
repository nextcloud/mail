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

namespace OCA\Mail\Tests\IMAP;

use Horde_Imap_Client_Base;
use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_Fetch_Results;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Model\IMAPMessage;
use PHPUnit_Framework_TestCase;

class MessageMapperTest extends PHPUnit_Framework_TestCase {

	/** @var MessageMapper */
	private $mapper;

	protected function setUp() {
		parent::setUp();

		$this->mapper = new MessageMapper();
	}

	public function testGetByIds() {
		$imapClient = $this->createMock(Horde_Imap_Client_Base::class);
		$mailbox = 'inbox';
		$ids = [1, 3];

		$fetchResults = new Horde_Imap_Client_Fetch_Results();
		$fetchResult1 = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$fetchResult2 = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$imapClient->expects($this->once())
			->method('fetch')
			->willReturn($fetchResults);
		$fetchResults[0] = $fetchResult1;
		$fetchResults[1] = $fetchResult2;
		$fetchResult1->expects($this->once())
			->method('getUid')
			->willReturn(1);
		$fetchResult2->expects($this->once())
			->method('getUid')
			->willReturn(3);
		$message1 = new IMAPMessage($imapClient, $mailbox, 1, $fetchResult1);
		$message2 = new IMAPMessage($imapClient, $mailbox, 3, $fetchResult2);
		$expected = [
			$message1,
			$message2,
		];

		$result = $this->mapper->findByIds($imapClient, $mailbox, $ids);

		$this->assertEquals($expected, $result);
	}

}
