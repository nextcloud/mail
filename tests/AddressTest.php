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

use Horde_Mail_Rfc822_Address;
use OCA\Mail\Address;

class AddressTest extends TestCase {

	public function testSerialization() {
		$address = new Address('Christoph Wurst', 'christoph@domain.tld');

		$expected = [
			'label' => 'Christoph Wurst',
			'email' => 'christoph@domain.tld',
		];
		$json = $address->jsonSerialize();

		$this->assertEquals($expected, $json);
	}

	public function testToHorde() {
		$address = new Address('Christoph Wurst', 'christoph@domain.tld');
		$expected = new Horde_Mail_Rfc822_Address('christoph@domain.tld');
		$expected->personal = 'Christoph Wurst';

		$horde = $address->toHorde();

		$this->assertEquals($expected, $horde);
	}

	public function testEqualsIdentical() {
		$address = new Address('Christoph Wurst', 'christoph@domain.tld');

		$equals = $address->equals($address);

		$this->assertTrue($equals);
	}

	public function testEquals() {
		$address1 = new Address('Christoph Wurst', 'christoph@domain1.tld');
		$address2 = new Address('Christoph Wurst', 'christoph@domain1.tld');

		$equals = $address1->equals($address2);

		$this->assertTrue($equals);
	}

	public function testDoesNotEqual() {
		$address1 = new Address('Christoph Wurst', 'christoph@domain1.tld');
		$address2 = new Address('Christoph Wurst', 'christoph@domain2.tld');

		$equals = $address1->equals($address2);

		$this->assertFalse($equals);
	}

	public function testDoesNotEqualBecauseDifferentLabel() {
		$address1 = new Address('Christoph Wurst', 'christoph@domain.tld');
		$address2 = new Address('Wurst Christoph', 'christoph@domain.tld');

		$equals = $address1->equals($address2);

		$this->assertFalse($equals);
	}

}
