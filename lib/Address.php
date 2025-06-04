<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail;

use Horde_Mail_Rfc822_Address;
use JsonSerializable;
use ReturnTypeWillChange;

/**
 * @psalm-immutable
 */
class Address implements JsonSerializable {
	public const TYPE_FROM = 0;
	public const TYPE_TO = 1;
	public const TYPE_CC = 2;
	public const TYPE_BCC = 3;

	/** @var Horde_Mail_Rfc822_Address */
	private $wrapped;

	private function __construct(Horde_Mail_Rfc822_Address $wrapped) {
		$this->wrapped = $wrapped;
	}

	public static function fromHorde(Horde_Mail_Rfc822_Address $horde): self {
		return new self($horde);
	}

	public static function fromRaw(string $label, string $email): self {
		$wrapped = new Horde_Mail_Rfc822_Address($email);
		// If no label is set we use the email
		if ($label !== $email) {
			$wrapped->personal = $label;
		}
		return new self($wrapped);
	}

	/**
	 * @return string|null
	 */
	public function getLabel(): ?string {
		$personal = $this->wrapped->personal;
		if ($personal === null) {
			// Fallback
			return $this->getEmail();
		}
		$personal = trim(explode('<', $personal)[0]); // Remove the email part if present
		return $personal;
	}

	/**
	 * @return string|null
	 */
	public function getCustomEmail(): ?string {
		$personal = $this->wrapped->personal;
		if ($personal === null) {
			// Fallback
			return null;
		}
		$parts = explode('<', $personal);
		if (count($parts) === 1) {
			return null;
		}
		$customEmail = trim($parts[1], '>');
		return $customEmail;
	}

	/**
	 * @return string|null
	 */
	public function getEmail(): ?string {
		$email = $this->wrapped->bare_address;
		if ($email === null) {
			return null;
		}
		// Lets make sure the e-mail is valid UTF-8 at all times
		// Try a soft conversion first (some installations, eg: Alpine linux,
		// have issues with the '//IGNORE' option)
		$utf8 = iconv('UTF-8', 'UTF-8', $email);
		if ($utf8 !== false) {
			return $utf8;
		}
		$utf8 = iconv('UTF-8', 'UTF-8//IGNORE', $email);
		if ($utf8 === false) {
			throw new \Exception("Email address <$email> could not be converted via iconv");
		}
		return $utf8;
	}

	/**
	 * @return Horde_Mail_Rfc822_Address
	 */
	public function toHorde(): Horde_Mail_Rfc822_Address {
		return $this->wrapped;
	}

	#[\Override]
	#[ReturnTypeWillChange]
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
	public function equals($object): bool {
		return $this->getEmail() === $object->getEmail()
			&& $this->getLabel() === $object->getLabel();
	}
}
