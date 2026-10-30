<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use Horde_Mail_Exception;
use Horde_Mail_Rfc822;
use Horde_Mail_Rfc822_Address;
use Horde_Mail_Rfc822_List;
use OCA\Mail\Db\AddressEntryType;
use OCA\Mail\Exception\InvalidAddressException;

class AddressEntryNormalizer {
	/** RFC 5321, section 4.5.3.1.3: a path has at most 256 octets, including the angle brackets */
	private const MAX_LENGTH = 254;

	/** Stricter than RFC 5322 and 6532, but some writing systems need the joiners U+200C and U+200D */
	private const DISALLOWED_CHARACTERS = '/(?![\x{200C}\x{200D}])[\p{Cc}\p{Cf}\p{Zl}\p{Zp}]/u';

	private const IDNA_OPTIONS = IDNA_NONTRANSITIONAL_TO_ASCII | IDNA_USE_STD3_RULES | IDNA_CHECK_BIDI | IDNA_CHECK_CONTEXTJ;

	/**
	 * @throws InvalidAddressException
	 */
	public function normalize(string $value, AddressEntryType $type): string {
		if (strlen($value) > self::MAX_LENGTH) {
			throw new InvalidAddressException('Email address or domain too long');
		}

		$normalized = match ($type) {
			AddressEntryType::Individual => $this->normalizeAddress($value),
			AddressEntryType::Domain => $this->normalizeDomain($value),
		};

		// IDN decoding can lengthen the value, and Oracle's columns count bytes
		if (strlen($normalized) > self::MAX_LENGTH) {
			throw new InvalidAddressException('Email address or domain too long');
		}

		return $normalized;
	}

	/**
	 * @return string|null the stored form of $value, or null if $value or $type is invalid
	 */
	public function tryNormalize(string $value, string $type): ?string {
		$entryType = AddressEntryType::tryFrom($type);
		if ($entryType === null) {
			return null;
		}

		try {
			return $this->normalize($value, $entryType);
		} catch (InvalidAddressException) {
			return null;
		}
	}

	/**
	 * @throws InvalidAddressException
	 */
	private function normalizeAddress(string $value): string {
		if (!mb_check_encoding($value, 'UTF-8')
			|| preg_match(self::DISALLOWED_CHARACTERS, $value) === 1) {
			throw new InvalidAddressException('Invalid email address');
		}

		$separator = strrpos($value, '@');
		if ($separator === false || $separator === 0) {
			throw new InvalidAddressException('Invalid email address');
		}

		$localPart = substr($value, 0, $separator);
		$domain = substr($value, $separator + 1);
		// Horde accepts a trailing dot, over-long labels and any address literal
		$host = $this->toAsciiHost($domain);
		if ($host === null) {
			throw new InvalidAddressException('Invalid email address');
		}

		$addresses = $this->parseAddressList($value)
			// The stored form of a sender drops the quotes around its local part, e.g. of "alice smith"@example.test
			?? $this->parseAddressList('"' . addcslashes($localPart, '"\\') . '"@' . $domain);
		$address = $addresses !== null && count($addresses) === 1 ? $addresses->first() : null;
		if (!($address instanceof Horde_Mail_Rfc822_Address)) {
			throw new InvalidAddressException('Invalid email address');
		}

		$address->host = $host;
		return $address->bare_address;
	}

	private function parseAddressList(string $value): ?Horde_Mail_Rfc822_List {
		try {
			// RFC 5322, with the UTF-8 addresses of RFC 6532
			return (new Horde_Mail_Rfc822())->parseAddressList($value, ['validate' => 'eai']);
		} catch (Horde_Mail_Exception) {
			return null;
		}
	}

	/**
	 * @throws InvalidAddressException
	 */
	private function normalizeDomain(string $domain): string {
		$host = $this->toAsciiHost($domain);
		if ($host === null) {
			throw new InvalidAddressException('Invalid domain');
		}

		// Same form as the host of a parsed sender: IDN-decoded and lowercased by Horde
		$address = new Horde_Mail_Rfc822_Address();
		$address->host = $host;
		return $address->host;
	}

	private function toAsciiHost(string $domain): ?string {
		if (str_starts_with($domain, '[') && str_ends_with($domain, ']')) {
			// RFC 5321, section 4.1.3, without IPv6 address literals: Horde can't parse a sender with one
			$literal = substr($domain, 1, -1);
			return filter_var($literal, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false ? $literal : null;
		}

		if ($domain === '') {
			return null;
		}

		$asciiDomain = idn_to_ascii($domain, self::IDNA_OPTIONS, INTL_IDNA_VARIANT_UTS46);
		// UTS #46 accepts a trailing dot, RFC 5322 doesn't
		if ($asciiDomain === false || str_ends_with($asciiDomain, '.')) {
			return null;
		}

		// RFC 3696, section 2: only an IPv4 address ends with an all-numeric label
		if (preg_match('/(^|\.)[0-9]+$/', $asciiDomain) === 1
			&& filter_var($asciiDomain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
			return null;
		}

		return $asciiDomain;
	}
}
