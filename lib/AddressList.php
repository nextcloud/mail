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
use Horde_Mail_Rfc822_Object;
use JsonSerializable;
use ReturnTypeWillChange;

/**
 * @psalm-immutable
 */
class AddressList implements Countable, JsonSerializable {
	/**
	 * @param Address[] $addresses
	 */
	public function __construct(
		private array $addresses = []
	) {
	}

	/**
	 * Parse an address (list) like "a@b.c" or "a@b.c, d@e.f"
	 *
	 * @param string|string[] $str address list string to parse
	 */
	public static function parse($str): \OCA\Mail\AddressList {
		$hordeList = new Horde_Mail_Rfc822_List($str);
		return self::fromHorde($hordeList);
	}

	/**
	 * Construct a new list from an horde list
	 */
	public static function fromHorde(Horde_Mail_Rfc822_List $hordeList): \OCA\Mail\AddressList {
		$addresses = array_map(Address::fromHorde(...), array_filter(iterator_to_array($hordeList), static fn (Horde_Mail_Rfc822_Object $obj): bool
			// TODO: how to handle non-addresses? This doesn't seem right …
			=> $obj instanceof Horde_Mail_Rfc822_Address));
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
	 */
	public function first(): ?\OCA\Mail\Address {
		if ($this->addresses === []) {
			return null;
		}

		return $this->addresses[0];
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
	 *
	 * @psalm-return \Generator<int, Address, mixed, void>
	 */
	public function iterate(): \Generator {
		foreach ($this->addresses as $address) {
			yield $address;
		}
	}

	public function merge(AddressList $other): \OCA\Mail\AddressList {
		$addresses = $this->addresses;

		foreach ($other->addresses as $address) {
			$same = array_filter($addresses, static fn (Address $our): bool
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
		$hordeAddresses = array_map(static fn (Address $address): \Horde_Mail_Rfc822_Address => $address->toHorde(), $this->addresses);
		return new Horde_Mail_Rfc822_List($hordeAddresses);
	}
}
