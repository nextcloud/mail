<?php

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 *
 */

namespace OCA\Mail\Tests\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\MailboxPrefixDetector;
use OCA\Mail\SearchFolder;

class MailboxPrefixDetectorTest extends TestCase {

	/** @var MailboxPrefixDetector */
	private $detector;

	protected function setUp() {
		parent::setUp();

		$this->detector = new MailboxPrefixDetector();
	}

	public function testDetectWithPrefix() {
		$folder1 = $this->createMock(Folder::class);
		$folder2 = $this->createMock(Folder::class);
		$folder1->expects($this->once())
			->method('getMailbox')
			->willReturn('INBOX');
		$folder1->expects($this->once())
			->method('getDelimiter')
			->willReturn('.');
		$folder2->expects($this->once())
			->method('getMailbox')
			->willReturn('INBOX.Sent');
		$folder2->expects($this->once())
			->method('getDelimiter')
			->willReturn('.');

		$havePrefix = $this->detector->havePrefix([
			$folder1,
			$folder2,
		]);

		$this->assertTrue($havePrefix);
	}

	public function testDetectWithoutPrefix() {
		$folder1 = $this->createMock(Folder::class);
		$folder2 = $this->createMock(Folder::class);
		$folder1->expects($this->once())
			->method('getMailbox')
			->willReturn('INBOX');
		$folder1->expects($this->once())
			->method('getDelimiter')
			->willReturn('.');
		$folder2->expects($this->once())
			->method('getMailbox')
			->willReturn('Sent');
		$folder2->expects($this->once())
			->method('getDelimiter')
			->willReturn('.');

		$havePrefix = $this->detector->havePrefix([
			$folder1,
			$folder2,
		]);

		$this->assertFalse($havePrefix);
	}

	public function testDetectWithSearchFolder() {
		$folder1 = $this->createMock(Folder::class);
		$folder2 = $this->createMock(SearchFolder::class);
		$folder1->expects($this->any())
			->method('getMailbox')
			->willReturn('INBOX');
		$folder1->expects($this->any())
			->method('getDelimiter')
			->willReturn('.');
		$folder2->expects($this->any())
			->method('getMailbox')
			->willReturn('INBOX/Flagged');
		$folder2->expects($this->any())
			->method('getDelimiter')
			->willReturn('.');

		$havePrefix = $this->detector->havePrefix([
			$folder1,
			$folder2,
		]);

		$this->assertTrue($havePrefix);
	}

}
