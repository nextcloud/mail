<?php

declare(strict_types=1);

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

namespace OCA\Mail\Tests\Unit\IMAP\Sync;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Base;
use Horde_Imap_Client_Data_Sync;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Mailbox;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\IMAP\Sync\Request;
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\IMAP\Sync\Synchronizer;
use PHPUnit\Framework\MockObject\MockObject;

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
			->with($this->equalTo(new Horde_Imap_Client_Mailbox('inbox')), $this->equalTo('123456'))
			->willReturn($hordeSync);
		$newMessages = [];
		$changedMessages = [];
		$vanishedMessageUids = [4, 5];
		$hordeSync->expects($this->exactly(3))
			->method('__get')
			->willReturnMap([
				['newmsgsuids', new Horde_Imap_Client_Ids($newMessages)],
				['flagsuids', new Horde_Imap_Client_Ids($changedMessages)],
				['vanisheduids', new Horde_Imap_Client_Ids($vanishedMessageUids)],
			]);
		$expected = new Response($newMessages, $changedMessages, $vanishedMessageUids);

		$response = $this->synchronizer->sync($imapClient, $request);

		$this->assertEquals($expected, $response);
	}

	public function compressionData(): array {
		return [
			[[1,2,3,4,5], '1:5'], // Contiguous range
			[[1,2,4,5], '1:2,4:5'], // Intermitted range
			[[], ''], // Empty range
			[[1], '1'] // Single value
		];
	}

	/**
	 * @dataProvider compressionData
	 */
	public function testSyncWithCompressedUids(array $uids, string $ranges): void {
		$imapClient = $this->createMock(Horde_Imap_Client_Base::class);
		$request = $this->createMock(Request::class);
		$request->expects($this->any())
			->method('getMailbox')
			->willReturn('inbox');
		$request->expects($this->once())
			->method('getToken')
			->willReturn('123456');
		$request->expects($this->once())
			->method('getUids')
			->willReturn($uids);
		$hordeSync = $this->createMock(Horde_Imap_Client_Data_Sync::class);
		$imapClient->expects($this->once())
			->method('sync')
			->with($this->equalTo(
				new Horde_Imap_Client_Mailbox('inbox')),
				$this->equalTo('123456'),
				$this->equalTo([
					'criteria' => 42,
					'ids' => new Horde_Imap_Client_Ids($ranges),
				])
			)
			->willReturn($hordeSync);
		$newMessages = [];
		$changedMessages = [];
		$vanishedMessageUids = [4, 5];
		$hordeSync->expects($this->exactly(3))
			->method('__get')
			->willReturnMap([
				['newmsgsuids', new Horde_Imap_Client_Ids($newMessages)],
				['flagsuids', new Horde_Imap_Client_Ids($changedMessages)],
				['vanisheduids', new Horde_Imap_Client_Ids($vanishedMessageUids)],
			]);
		$expected = new Response($newMessages, $changedMessages, $vanishedMessageUids);

		$response = $this->synchronizer->sync($imapClient, $request);

		$this->assertEquals($expected, $response);
	}
}
