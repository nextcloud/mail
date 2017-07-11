<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Clement Wong <mail@clement.hk>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OCA\Mail\Tests\Imap;

/**
 * @group IMAP
 */
class AccountTest extends AbstractTest {

	/**
	 * @dataProvider providesMailBoxNames
	 * @param $name
	 */
	public function testCreateAndDelete($name) {
		$name = uniqid($name);
		$this->createMailBox($name);
		$this->assertMailBoxExists($name);

		$this->getTestAccount()->deleteMailbox($name);
		$this->assertMailBoxNotExists($name);
	}

	public function providesMailBoxNames() {
		return [
			['boxbox'],
			['box box'],
			['äöü']
		];
	}

	/**
	 * @dataProvider providesMailBoxNames
	 * @param $name
	 */
	public function testListMessages($name) {
		$name = uniqid($name);
		$newMailBox = parent::createMailBox($name);
		$count = $newMailBox->getTotalMessages();
		$this->assertEquals(0, $count);
		$messages = $newMailBox->getMessages();
		$this->assertInternalType('array', $messages);
		$this->assertEquals(0, count($messages));
		$this->createTestMessage($newMailBox);
		$count = $newMailBox->getTotalMessages();
		$this->assertEquals(1, $count);
		$messages = $newMailBox->getMessages();
		$this->assertInternalType('array', $messages);
		$this->assertEquals(1, count($messages));
	}

}
