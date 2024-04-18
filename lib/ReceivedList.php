<?php

declare(strict_types=1);

/**
 * @copyright 2023 Hamza Mahjoubi <hamzamahjoubi221@proton.me>
 *
 * @author 2023 Hamza Mahjoubi <hamzamahjoubi221@proton.me>
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
use Horde_Mime_Headers_Received;
use JsonSerializable;
use ReturnTypeWillChange;

/**
 * @psalm-immutable
 */
class ReceivedList implements Countable, JsonSerializable {
	
	/** @var Received[] */
	private $received;

	/**
	 * @param Received[] $received
	 */
	public function __construct(array $received = []) {
		$this->received = $received;
	}

	/**
	 * Construct a new list from an horde list
	 *
	 * @param Horde_Mime_Headers_Received $hordeList
	 * @return ReceivedList
	 */
	public static function fromHorde(Horde_Mime_Headers_Received $horde) {
		$hordeList = $horde->__get('value');
		$received = array_map(static function (string $received) {
			return Received::fromHorde($received);
		}, array_filter($hordeList, static function (string $entry) {
			return strpos($entry, 'from') === 0;
		}));
		return new ReceivedList($received);
	}



	/**
	 * Get first element
	 *
	 * Returns null if the list is empty
	 *
	 * @return Received|null
	 */
	public function first() {
		if ($this->received === []) {
			return null;
		}

		return $this->received[0];
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return array_map(static function (Received $entry) {
			return $entry->jsonSerialize();
		}, $this->received);
	}


	/**
	 * @return int
	 */
	#[ReturnTypeWillChange]
	public function count() {
		return count($this->received);
	}

	/**
	 * Iterate over the internal list of received using a generator method
	 *
	 * @return \Generator
	 *
	 * @psalm-return \Generator<int, Received, mixed, void>
	 */
	public function iterate(): \Generator {
		foreach ($this->received as $entry) {
			yield $entry;
		}
	}

}
