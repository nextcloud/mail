<?php

declare(strict_types=1);

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

namespace OCA\Mail;

use Countable;
use Horde_Mail_Rfc822_Address;
use Horde_Mail_Rfc822_List;
use Horde_Mail_Rfc822_Object;
use JsonSerializable;
use ReturnTypeWillChange;

/**
 * @psalm-immutable
 */
class AddressList implements Countable, JsonSerializable {
	/** @var Address[] */
	private $addresses;

	/**
	 * @param Address[] $addresses
	 */
	public function __construct(array $addresses = []) {
		$this->addresses = $addresses;
	}

	/**
	 * Parse an address (list) like "a@b.c" or "a@b.c, d@e.f"
	 *
	 * @param string|string[] $str address list string to parse
	 * @return AddressList
	 */
	public static function parse($str) {
		$hordeList = new Horde_Mail_Rfc822_List($str);
		return self::fromHorde($hordeList);
	}

	/**
	 * Construct a new list from an horde list
	 *
	 * @param Horde_Mail_Rfc822_List $hordeList
	 * @return AddressList
	 */
	public static function fromHorde(Horde_Mail_Rfc822_List $hordeList) {
		$addresses = array_map(function (Horde_Mail_Rfc822_Address $addr) {
			return Address::fromHorde($addr);
		}, array_filter(iterator_to_array($hordeList), function (Horde_Mail_Rfc822_Object $obj) {
			// TODO: how to handle non-addresses? This doesn't seem right …
			return $obj instanceof Horde_Mail_Rfc822_Address;
		}));
		return new AddressList($addresses);
	}

	public static function fromRow(array $recipient): self {
		return new self([
			Address::fromRaw($recipient['label'], $recipient['email'])
		]);
	}

	/**
	 * Get first element
	 *
	 * Returns null if the list is empty
	 *
	 * @return Address|null
	 */
	public function first() {
		if (empty($this->addresses)) {
			return null;
		}

		return $this->addresses[0];
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return array_map(function (Address $address) {
			return $address->jsonSerialize();
		}, $this->addresses);
	}

	/**
	 * @return int
	 */
	#[ReturnTypeWillChange]
	public function count() {
		return count($this->addresses);
	}

	/**
	 * Iterate over the internal list of addresses using a generator method
	 *
	 * @return \Generator
	 *
	 * @psalm-return \Generator<int, Address, mixed, void>
	 */
	public function iterate(): \Generator {
		foreach ($this->addresses as $address) {
			yield $address;
		}
	}

	/**
	 * @param AddressList $other
	 * @return AddressList
	 */
	public function merge(AddressList $other) {
		$addresses = $this->addresses;

		foreach ($other->addresses as $address) {
			$same = array_filter($addresses, function (Address $our) use ($address) {
				// Check whether our array contains the other address
				return $our->equals($address);
			});
			if (empty($same)) {
				// No dup found, hence the address is new and we
				// have to add it
				$addresses[] = $address;
			}
		}

		return new AddressList($addresses);
	}

	/**
	 * @return Horde_Mail_Rfc822_List
	 */
	public function toHorde() {
		$hordeAddresses = array_map(function (Address $address) {
			return $address->toHorde();
		}, $this->addresses);
		return new Horde_Mail_Rfc822_List($hordeAddresses);
	}
}
