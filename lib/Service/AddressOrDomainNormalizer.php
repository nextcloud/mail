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
use OCA\Mail\Exception\InvalidAddressException;

class AddressOrDomainNormalizer {
	private const TYPE_INDIVIDUAL = 'individual';
	private const TYPE_DOMAIN = 'domain';

	/** RFC 5321, section 4.5.3.1.3: a path has at most 256 octets, including the angle brackets */
	private const MAX_LENGTH = 254;

	/** Stricter than RFC 5322 and 6532, but some writing systems need the joiners U+200C and U+200D */
	private const DISALLOWED_CHARACTERS = '/(?![\x{200C}\x{200D}])[\p{Cc}\p{Cf}\p{Zl}\p{Zp}]/u';

	private const IDNA_OPTIONS = IDNA_NONTRANSITIONAL_TO_ASCII | IDNA_USE_STD3_RULES | IDNA_CHECK_BIDI | IDNA_CHECK_CONTEXTJ;

	/**
	 * @throws InvalidAddressException
	 */
	public function normalize(string $value, string $type): string {
		$normalized = match ($type) {
			self::TYPE_INDIVIDUAL => $this->normalizeAddress($value),
			self::TYPE_DOMAIN => $this->normalizeDomain($value),
			default => throw new InvalidAddressException('Invalid type'),
		};

		// IDN decoding can lengthen the value, and Oracle's columns count bytes
		if (strlen($normalized) > self::MAX_LENGTH) {
			throw new InvalidAddressException('Email address or domain too long');
		}

		return $normalized;
	}

	/**
	 * @throws InvalidAddressException
	 */
	private function normalizeAddress(string $value): string {
		if (strlen($value) > self::MAX_LENGTH
			|| !mb_check_encoding($value, 'UTF-8')
			|| preg_match(self::DISALLOWED_CHARACTERS, $value) === 1) {
			throw new InvalidAddressException('Invalid email address');
		}

		try {
			// RFC 5322, with the UTF-8 addresses of RFC 6532
			$addresses = (new Horde_Mail_Rfc822())->parseAddressList($value, ['validate' => 'eai']);
		} catch (Horde_Mail_Exception) {
			throw new InvalidAddressException('Invalid email address');
		}

		$address = $addresses->first();
		$separator = strrpos($value, '@');
		// Horde accepts a trailing dot, over-long labels and any address literal
		$host = $separator === false ? null : $this->toAsciiHost(substr($value, $separator + 1));
		if (count($addresses) !== 1
			|| !($address instanceof Horde_Mail_Rfc822_Address)
			|| $host === null) {
			throw new InvalidAddressException('Invalid email address');
		}

		$address->host = $host;
		return $address->bare_address;
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
			$literal = substr($domain, 1, -1);
			return $this->isValidAddressLiteral($literal) ? $literal : null;
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

	/**
	 * RFC 5321, section 4.1.3
	 */
	private function isValidAddressLiteral(string $literal): bool {
		if (stripos($literal, 'IPv6:') === 0) {
			return filter_var(substr($literal, 5), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
		}

		return filter_var($literal, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
	}
}
