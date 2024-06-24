<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Mail_Rfc822_Address;
use OCA\Mail\Address;

class AddressTest extends TestCase {
	public function testSerialization() {
		$address = Address::fromRaw('Christoph Wurst', 'christoph@domain.tld');

		$expected = [
			'label' => 'Christoph Wurst',
			'email' => 'christoph@domain.tld',
		];
		$json = $address->jsonSerialize();

		$this->assertEquals($expected, $json);
	}

	public function testToHorde() {
		$address = Address::fromRaw('Christoph Wurst', 'christoph@domain.tld');
		$expected = new Horde_Mail_Rfc822_Address('christoph@domain.tld');
		$expected->personal = 'Christoph Wurst';

		$horde = $address->toHorde();

		$this->assertEquals($expected, $horde);
	}

	public function testEqualsIdentical() {
		$address = Address::fromRaw('Christoph Wurst', 'christoph@domain.tld');

		$equals = $address->equals($address);

		$this->assertTrue($equals);
	}

	public function testEquals() {
		$address1 = Address::fromRaw('Christoph Wurst', 'christoph@domain1.tld');
		$address2 = Address::fromRaw('Christoph Wurst', 'christoph@domain1.tld');

		$equals = $address1->equals($address2);

		$this->assertTrue($equals);
	}

	public function testDoesNotEqual() {
		$address1 = Address::fromRaw('Christoph Wurst', 'christoph@domain1.tld');
		$address2 = Address::fromRaw('Christoph Wurst', 'christoph@domain2.tld');

		$equals = $address1->equals($address2);

		$this->assertFalse($equals);
	}

	public function testDoesNotEqualBecauseDifferentLabel() {
		$address1 = Address::fromRaw('Christoph Wurst', 'christoph@domain.tld');
		$address2 = Address::fromRaw('Wurst Christoph', 'christoph@domain.tld');

		$equals = $address1->equals($address2);

		$this->assertFalse($equals);
	}
}
