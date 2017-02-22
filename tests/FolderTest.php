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

namespace OCA\Mail\Tests;

use Horde_Imap_Client_Mailbox;
use OCA\Mail\Account;
use OCA\Mail\Folder;
use PHPUnit_Framework_MockObject_MockObject;
use Test\TestCase;

class FolderTest extends TestCase {

	/** @var Account|PHPUnit_Framework_MockObject_MockObject */
	private $account;

	/** @var Horde_Imap_Client_Mailbox|PHPUnit_Framework_MockObject_MockObject */
	private $mailbox;

	/** @var Folder */
	private $folder;

	private function mockFolder(array $attributes = [], $delimiter = '.') {
		$this->account = $this->createMock(Account::class);
		$this->mailbox = $this->createMock(Horde_Imap_Client_Mailbox::class);

		$this->folder = new Folder($this->account, $this->mailbox, $attributes, $delimiter);
	}

	public function testGetMailbox() {
		$this->mockFolder();
		$this->mailbox->expects($this->once())
			->method('__get')
			->with($this->equalTo('utf8'))
			->willReturn('Sent');

		$this->assertSame('Sent', $this->folder->getMailbox());
	}

	public function testGetDelimiter() {
		$this->mockFolder([], ',');

		$this->assertSame(',', $this->folder->getDelimiter());
	}

	public function testGetAttributes() {
		$this->mockFolder(['\noselect']);

		$this->assertSame(['\noselect'], $this->folder->getAttributes());
	}

	public function testAddFolder() {
		$this->mockFolder(['\noselect']);
		$subFolder = $this->createMock(Folder::class);
		$subFolder->expects($this->once())
			->method('getMailbox')
			->willReturn('INBOX/FLAGED');

		$this->folder->addFolder($subFolder);

		$this->assertCount(1, $this->folder->getFolders());
	}

	public function testSetStatus() {
		$this->mockFolder();

		$this->folder->setStatus([
			'unseen' => 4,
		]);
	}

	public function testSpecialUse() {
		$this->mockFolder();

		$this->folder->addSpecialUse('flagged');

		$this->assertCount(1, $this->folder->getSpecialUse());
		$this->assertSame('flagged', $this->folder->getSpecialUse()[0]);
	}

	public function testDisplayName() {
		$this->mockFolder();

		$this->folder->setDisplayName('Eingang');
		$this->assertSame('Eingang', $this->folder->getDisplayName());
	}

	public function testIsSearchable() {
		$this->mockFolder([]);

		$this->assertTrue($this->folder->isSearchable());
	}

	public function testIsNotSearchable() {
		$this->mockFolder(['\noselect']);

		$this->assertFalse($this->folder->isSearchable());
	}

	public function testJsonSerialize() {
		$this->mockFolder();
		$subFolder = $this->createMock(Folder::class);

		$subFolder->expects($this->exactly(2))
			->method('getMailbox')
			->willReturn('Archive');
		$subFolder->expects($this->once())
			->method('jsonSerialize')
			->willReturn(['subdir data']);
		$this->mailbox->expects($this->once())
			->method('__get')
			->with($this->equalTo('utf8'))
			->willReturn('Sent');
		$this->account->expects($this->once())
			->method('getId')
			->willReturn(123);

		$this->folder->setDisplayName('Gesendet');
		$this->folder->addSpecialUse('sent');
		$this->folder->setStatus([
			'unseen' => 13,
			'messages' => 333,
		]);
		$this->folder->addFolder($subFolder);

		$expected = [
			'id' => base64_encode('Sent'),
			'accountId' => 123,
			'name' => 'Gesendet',
			'specialRole' => null,
			'unseen' => 13,
			'total' => 333,
			'isEmpty' => false,
			'noSelect' => false,
			'attributes' => [],
			'delimiter' => '.',
			'folders' => [['subdir data']],
			'specialRole' => 'sent',
		];
		$this->assertEquals($expected, $this->folder->jsonSerialize());
	}

}
