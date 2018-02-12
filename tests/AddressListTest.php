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

namespace OCA\Mail\Tests;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Mail_Rfc822_Address;
use Horde_Mail_Rfc822_Group;
use Horde_Mail_Rfc822_List;
use OCA\Mail\Address;
use OCA\Mail\AddressList;

class AddressListTest extends TestCase {

	public function testSerialize() {
		$list = new AddressList([
			new Address('User 1', 'user1@domain.tld'),
			new Address('User 2', 'user2@domain.tld'),
		]);
		$expected = [
			[
				'label' => 'User 1',
				'email' => 'user1@domain.tld',
			],
			[
				'label' => 'User 2',
				'email' => 'user2@domain.tld',
			],
		];

		$json = $list->jsonSerialize();

		$this->assertCount(2, $json);
		$this->assertEquals($expected, $json);
	}

	public function testParseSingleAddress() {
		$source = 'a@b.c';
		$expected = new AddressList([
			new Address('a@b.c', 'a@b.c'),
		]);

		$list = AddressList::parse($source);

		$this->assertEquals($expected, $list);
	}

	public function testFromHordeList() {
		$hordeList = new Horde_Mail_Rfc822_List([
			new Horde_Mail_Rfc822_Address(),
			new Horde_Mail_Rfc822_Group(),
		]);

		$list = AddressList::fromHorde($hordeList);

		$this->assertCount(1, $list);
	}

	public function testToHorde() {
		$list = new AddressList([
			new Address('A', 'a@domain.tld'),
			new Address('B', 'b@domain.tld'),
		]);
		$add1 = new Horde_Mail_Rfc822_Address('a@domain.tld');
		$add1->personal = 'A';
		$add2 = new Horde_Mail_Rfc822_Address('b@domain.tld');
		$add2->personal = 'B';
		$expected = new Horde_Mail_Rfc822_List([
			$add1,
			$add2
		]);

		$hordeList = $list->toHorde();

		$this->assertEquals($expected, $hordeList);
	}

	public function testFromAndToHorde() {
		$add1 = new Horde_Mail_Rfc822_Address('a@domain.tld');
		$add1->personal = 'A';
		$add2 = new Horde_Mail_Rfc822_Address('b@domain.tld');
		$add2->personal = 'B';
		$source = new Horde_Mail_Rfc822_List([
			$add1,
			$add2,
		]);
		$expected = new Horde_Mail_Rfc822_List([
			$add1,
			$add2,
		]);
		$list = AddressList::fromHorde($source);

		$hordeList = $list->toHorde();

		$this->assertEquals($expected, $hordeList);
	}

	public function testMergeIdentical() {
		$a = new AddressList([
			new Address('A', 'a@b.c'),
		]);
		$b = new AddressList([
			new Address('A', 'a@b.c'),
		]);

		$c = $a->merge($b);

		$this->assertCount(1, $c);
	}

	public function testMergeNonIdentical() {
		$a = new AddressList([
			new Address('A', 'a@b.c'),
		]);
		$b = new AddressList([
			new Address('B', 'b@b.c'),
		]);

		$c = $a->merge($b);

		$this->assertCount(1, $a);
		$this->assertCount(1, $b);
		$this->assertCount(2, $c);
	}

	public function testMergeMixed() {
		$a = new AddressList([
			new Address('A', 'a@b.c'),
			new Address('B', 'b@b.c'),
		]);
		$b = new AddressList([
			new Address('B', 'b@b.c'),
		]);

		$c = $a->merge($b);

		$this->assertCount(2, $c);
	}

	public function testMergeEmpty() {
		$a = new AddressList([
			new Address('A', 'a@b.c'),
			new Address('B', 'b@b.c'),
		]);
		$b = new AddressList();

		$c = $a->merge($b);

		$this->assertCount(2, $c);
	}

	public function testMergeToEmpty() {
		$a = new AddressList([
		]);
		$b = new AddressList([
			new Address('A', 'a@b.c'),
			new Address('B', 'b@b.c'),
		]);

		$c = $a->merge($b);

		$this->assertCount(2, $c);
	}

}
