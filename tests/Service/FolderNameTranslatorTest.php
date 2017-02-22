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

use OCA\Mail\Folder;
use OCA\Mail\SearchFolder;
use OCA\Mail\Service\FolderNameTranslator;
use OCP\IL10N;
use PHPUnit_Framework_MockObject_MockObject;
use Test\TestCase;

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

	public function testTranslateAll() {
		$folder = $this->createMock(Folder::class);

		$folder->expects($this->once())
			->method('getSpecialUse')
			->willReturn([]);

		$this->translator->translateAll([$folder]);
	}

	public function testTranslateSpecialUse() {
		$folder = $this->createMock(Folder::class);

		$folder->expects($this->once())
			->method('getSpecialUse')
			->willReturn(['flagged']);

		$this->translator->translate($folder);
	}

}
