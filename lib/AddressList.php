<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail;

use Countable;
use Horde_Mail_Rfc822_Address;
use Horde_Mail_Rfc822_List;
use JsonSerializable;
use ReturnTypeWillChange;

/**
 * @psalm-immutable
 */
class AddressList implements Countable, JsonSerializable {
	/** @var list<Address> */
	private array $addresses;

	/**
	 * @param list<Address> $addresses
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
	 */
	public static function fromHorde(Horde_Mail_Rfc822_List $hordeList): self {
		$hordeObjects = iterator_to_array($hordeList, false);
		$hordeAddresses = array_values(array_filter($hordeObjects, static fn (mixed $obj) => $obj instanceof Horde_Mail_Rfc822_Address));
		$addresses = array_map(static fn (Horde_Mail_Rfc822_Address $addr) => Address::fromHorde($addr), $hordeAddresses);
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
	public function first(): ?Address {
		foreach ($this->addresses as $address) {
			return $address;
		}
		return null;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return array_map(static fn (Address $address) => $address->jsonSerialize(), $this->addresses);
	}

	/**
	 * @return int
	 */
	#[\Override]
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
			$same = array_filter($addresses, static fn (Address $our)
				// Check whether our array contains the other address
				=> $our->equals($address));
			if ($same === []) {
				// No dup found, hence the address is new and we
				// have to add it
				$addresses[] = $address;
			}
		}

		return new AddressList($addresses);
	}

	public function toHorde(): Horde_Mail_Rfc822_List {
		$hordeAddresses = array_map(static fn (Address $address) => $address->toHorde(), $this->addresses);
		return new Horde_Mail_Rfc822_List($hordeAddresses);
	}
}
