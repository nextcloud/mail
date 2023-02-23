<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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

namespace OCA\Mail\Tests\Unit\IMAP\Sync;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client;
use Horde_Imap_Client_Base;
use Horde_Imap_Client_Data_Capability;
use Horde_Imap_Client_Data_Sync;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Mailbox;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\IMAP\Sync\Request;
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\IMAP\Sync\Synchronizer;
use PHPUnit\Framework\MockObject\MockObject;
use function range;

class SynchronizerTest extends TestCase {
	/** @var MessageMapper|MockObject */
	private $mapper;

	/** @var Synchronizer */
	private $synchronizer;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(MessageMapper::class);

		$this->synchronizer = new Synchronizer($this->mapper);
	}

	public function testSyncWithQresync(): void {
		$imapClient = $this->createMock(Horde_Imap_Client_Base::class);
		$request = $this->createMock(Request::class);
		$request->expects($this->any())
			->method('getMailbox')
			->willReturn('inbox');
		$request->expects($this->once())
			->method('getToken')
			->willReturn('123456');
		$hordeSync = $this->createMock(Horde_Imap_Client_Data_Sync::class);
		$capabilities = $this->createMock(Horde_Imap_Client_Data_Capability::class);
		$imapClient->expects(self::once())
			->method('__get')
			->with('capability')
			->willReturn($capabilities);
		$capabilities->expects(self::once())
			->method('isEnabled')
			->with('QRESYNC')
			->willReturn(true);
		$imapClient->expects($this->once())
			->method('sync')
			->with($this->equalTo(new Horde_Imap_Client_Mailbox('inbox')), $this->equalTo('123456'))
			->willReturn($hordeSync);
		$newMessages = [];
		$changedMessages = [];
		$vanishedMessageUids = [4, 5];
		$hordeSync->expects($this->once())
			->method('__get')
			->with('vanisheduids')
			->willReturn(new Horde_Imap_Client_Ids($vanishedMessageUids));
		$expected = new Response($newMessages, $changedMessages, $vanishedMessageUids);

		$response = $this->synchronizer->sync(
			$imapClient,
			$request,
			'user',
			Horde_Imap_Client::SYNC_VANISHEDUIDS
		);

		$this->assertEquals($expected, $response);
	}

	public function testSyncChunked(): void {
		$imapClient = $this->createMock(Horde_Imap_Client_Base::class);
		$request = $this->createMock(Request::class);
		$request->method('getMailbox')
			->willReturn('inbox');
		$request->method('getToken')
			->willReturn('123456');
		$request->method('getUids')
			->willReturn(range(1, 30000, 1));
		$capabilities = $this->createMock(Horde_Imap_Client_Data_Capability::class);
		$imapClient->expects(self::once())
			->method('__get')
			->with('capability')
			->willReturn($capabilities);
		$capabilities->expects(self::once())
			->method('isEnabled')
			->with('QRESYNC')
			->willReturn(false);
		$hordeSync = $this->createMock(Horde_Imap_Client_Data_Sync::class);
		$imapClient->expects($this->exactly(3))
			->method('sync')
			->with($this->equalTo(new Horde_Imap_Client_Mailbox('inbox')), $this->equalTo('123456'))
			->willReturn($hordeSync);
		$newMessages = $changedMessages = $vanishedMessageUids = [];
		$hordeSync->expects($this->any())
			->method('__get')
			->willReturn(new Horde_Imap_Client_Ids([]));
		$expected = new Response($newMessages, $changedMessages, $vanishedMessageUids);

		$response = $this->synchronizer->sync(
			$imapClient,
			$request,
			'user',
			Horde_Imap_Client::SYNC_VANISHEDUIDS
		);

		$this->assertEquals($expected, $response);
	}
}
