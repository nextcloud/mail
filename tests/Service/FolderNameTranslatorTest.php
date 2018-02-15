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

namespace OCA\Mail\Tests\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Folder;
use OCA\Mail\Service\FolderNameTranslator;
use OCP\IL10N;
use PHPUnit_Framework_MockObject_MockObject;

class FolderNameTranslatorTest extends TestCase {

	/** @var IL10n|PHPUnit_Framework_MockObject_MockObject */
	private $l10n;

	/** @var FolderNameTranslator */
	private $translator;

	protected function setUp() {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->expects($this->any())
			->method('t')
			->willReturnCallback(function($string) {
				return "Translated $string";
			});

		$this->translator = new FolderNameTranslator($this->l10n);
	}

	public function testTranslate() {
		$folder = $this->createMock(Folder::class);
		$folder->expects($this->any())
			->method('getDelimiter')
			->willReturn('.');
		$folder->expects($this->any())
			->method('getMailbox')
			->willReturn('Archive');
		$folder->expects($this->once())
			->method('getSpecialUse')
			->willReturn([]);
		$folder->expects($this->once())
			->method('setDisplayName')
			->with('Archive');

		$this->translator->translateAll([$folder], false);
	}

	public function testTranslatePrefixed() {
		$folder1 = $this->createMock(Folder::class);
		$folder2 = $this->createMock(Folder::class);
		$folder1->expects($this->any())
			->method('getDelimiter')
			->willReturn('.');
		$folder2->expects($this->any())
			->method('getDelimiter')
			->willReturn('.');
		$folder1->expects($this->any())
			->method('getMailbox')
			->willReturn('INBOX');
		$folder2->expects($this->any())
			->method('getMailbox')
			->willReturn('INBOX.Sent');
		$folder1->expects($this->once())
			->method('getSpecialUse')
			->willReturn(['inbox']);
		$folder2->expects($this->once())
			->method('getSpecialUse')
			->willReturn([]);
		$folder1->expects($this->once())
			->method('setDisplayName')
			->with('Translated Inbox');
		$folder2->expects($this->once())
			->method('setDisplayName')
			->with('Sent');

		$this->translator->translateAll([$folder1, $folder2], true);
	}

	public function testTranslateSpecialUse() {
		$folder = $this->createMock(Folder::class);
		$folder->expects($this->any())
			->method('getMailbox')
			->willReturn('Sent');
		$folder->expects($this->once())
			->method('getSpecialUse')
			->willReturn(['sent']);
		$folder->expects($this->once())
			->method('setDisplayName')
			->with('Translated Sent');

		$this->translator->translateAll([$folder], false);
	}

}
