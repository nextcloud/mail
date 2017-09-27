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

namespace OCA\Mail;

use Horde_Mail_Rfc822_Address;
use JsonSerializable;
use SGH\Comparable\Comparable;

class Address implements JsonSerializable {

	/** @var Horde_Mail_Rfc822_Address */
	private $wrapped;

	/**
	 * @param string $label
	 * @param string $email
	 */
	public function __construct($label, $email) {
		$this->wrapped = new Horde_Mail_Rfc822_Address($email);
		// If no label is set we use the email
		if ($label !== $email && !is_null($label)) {
			$this->wrapped->personal = $label;
		}
	}

	/**
	 * @return string
	 */
	public function getLabel() {
		$personal = $this->wrapped->personal;
		if (is_null($personal)) {
			// Fallback
			return $this->getEmail();
		}
		return $personal;
	}

	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->wrapped->bare_address;
	}

	/**
	 * @return Horde_Mail_Rfc822_Address
	 */
	public function toHorde() {
		return $this->wrapped;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'label' => $this->getLabel(),
			'email' => $this->getEmail(),
		];
	}

	/**
	 * @param Address $object
	 * @return boolean
	 */
	public function equals($object) {
		return $this->getEmail() === $object->getEmail()
			&& $this->getLabel() === $object->getLabel();
	}

}
