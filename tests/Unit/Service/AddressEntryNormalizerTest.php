<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\AddressList;
use OCA\Mail\Db\AddressEntryType;
use OCA\Mail\Exception\InvalidAddressException;
use OCA\Mail\Service\AddressEntryNormalizer;

class AddressEntryNormalizerTest extends TestCase {
	private AddressEntryNormalizer $normalizer;

	protected function setUp(): void {
		parent::setUp();

		$this->normalizer = new AddressEntryNormalizer();
	}

	public static function provideValidAddresses(): array {
		return [
			'plain address' => ['alice@example.test', 'alice@example.test'],
			'host without a dot' => ['root@localhost', 'root@localhost'],
			'IPv4 address' => ['bob@192.168.0.1', 'bob@192.168.0.1'],
			'IPv4 address literal' => ['bob@[192.168.0.1]', 'bob@192.168.0.1'],
			'quoted local part' => ['"alice smith"@example.test', 'alice smith@example.test'],
			'internationalized address' => ['jöran@bücher.example', 'jöran@bücher.example'],
			'punycode domain' => ['alice@xn--bcher-kva.example', 'alice@bücher.example'],
			'decomposed domain' => ["alice@bu\u{0308}cher.example", 'alice@bücher.example'],
			'fullwidth full stop' => ["alice@example\u{FF0E}test", 'alice@example.test'],
			'upper case' => ['ALICE@Example.TEST', 'ALICE@example.test'],
			'digit zero' => ['alice0@example0.test', 'alice0@example0.test'],
			'zero width non-joiner' => ["\u{0639}\u{0644}\u{06CC}\u{200C}\u{0631}\u{0636}\u{0627}@example.test", "\u{0639}\u{0644}\u{06CC}\u{200C}\u{0631}\u{0636}\u{0627}@example.test"],
			'zero width joiner' => ["\u{0915}\u{094D}\u{200D}\u{0937}@example.test", "\u{0915}\u{094D}\u{200D}\u{0937}@example.test"],
			'local part over 64 characters' => [str_repeat('a', 65) . '@example.test', str_repeat('a', 65) . '@example.test'],
			// The stored form of a sender keeps a local part that needs quotes without them
			'unquoted space' => ['alice smith@example.test', 'alice smith@example.test'],
			'unquoted spaces' => ['apple.com    eqe@example.test', 'apple.com    eqe@example.test'],
			'unquoted parentheses' => ['(work)alice@example.test', '(work)alice@example.test'],
			'unquoted at sign' => ['alice@bob@example.test', 'alice@bob@example.test'],
			'unquoted quotation mark' => ['a"b@example.test', 'a"b@example.test'],
		];
	}

	/**
	 * @dataProvider provideValidAddresses
	 */
	public function testNormalizesValidAddress(string $address, string $expected): void {
		$normalized = $this->normalizer->normalize($address, AddressEntryType::Individual);

		$this->assertSame($expected, $normalized);
	}

	public static function provideInvalidAddresses(): array {
		return [
			'empty' => [''],
			'missing local part' => ['@example.test'],
			'missing at sign' => ['alice'],
			'missing domain' => ['alice@'],
			'trailing dot' => ['alice@example.test.'],
			'label over 63 characters' => ['alice@' . str_repeat('b', 64) . '.test'],
			'display name' => ['Alice <alice@example.test>'],
			'two addresses' => ['alice@example.test, bob@example.test'],
			'group' => ['friends: alice@example.test;'],
			'trailing comment' => ['alice@example.test (work)'],
			'space after the at sign' => ['alice@ example.test'],
			'invalid UTF-8' => ["al\xFFice@example.test"],
			'null character' => ["\"alice\x00smith\"@example.test"],
			'line break' => ["\"alice\r\nsmith\"@example.test"],
			'delete character' => ["\"alice\x7Fsmith\"@example.test"],
			'tab' => ["\"alice\tsmith\"@example.test"],
			'C1 control character' => ["\"alice\u{0085}smith\"@example.test"],
			'line separator' => ["alice\u{2028}smith@example.test"],
			'paragraph separator' => ["alice\u{2029}smith@example.test"],
			'right-to-left override' => ["alice\u{202E}smith@example.test"],
			'zero width space' => ["alice\u{200B}smith@example.test"],
			'invalid IPv4 literal' => ['bob@[300.1.1.1]'],
			'IPv6 address literal' => ['bob@[IPv6:2001:db8::1]'],
			'all-numeric top-level domain' => ['alice@example.123'],
		];
	}

	/**
	 * @dataProvider provideInvalidAddresses
	 */
	public function testRejectsInvalidAddress(string $address): void {
		$this->expectException(InvalidAddressException::class);
		$this->expectExceptionMessage('Invalid email address');

		$this->normalizer->normalize($address, AddressEntryType::Individual);
	}

	public static function provideValidDomains(): array {
		return [
			'domain' => ['example.test', 'example.test'],
			'subdomain' => ['mail.example.test', 'mail.example.test'],
			'host without a dot' => ['localhost', 'localhost'],
			'upper case' => ['Example.TEST', 'example.test'],
			'internationalized domain' => ['bücher.example', 'bücher.example'],
			'punycode domain' => ['xn--bcher-kva.example', 'bücher.example'],
			'upper-case punycode domain' => ['XN--BCHER-KVA.example', 'bücher.example'],
			'decomposed domain' => ["bu\u{0308}cher.example", 'bücher.example'],
			'fullwidth full stop' => ["example\u{FF0E}test", 'example.test'],
			'right-to-left domain' => ["\u{05D0}\u{05D1}.example", "\u{05D0}\u{05D1}.example"],
			'IPv4 address' => ['192.168.0.1', '192.168.0.1'],
			'IPv4 address literal' => ['[192.168.0.1]', '192.168.0.1'],
		];
	}

	/**
	 * @dataProvider provideValidDomains
	 */
	public function testNormalizesValidDomain(string $domain, string $expected): void {
		$normalized = $this->normalizer->normalize($domain, AddressEntryType::Domain);

		$this->assertSame($expected, $normalized);
	}

	public static function provideInvalidDomains(): array {
		return [
			'spaces' => ['not valid'],
			'empty' => [''],
			'trailing dot' => ['example.test.'],
			'trailing ideographic full stop' => ["example.test\u{3002}"],
			'leading dot' => ['.example.test'],
			'leading at sign' => ['@example.test'],
			'email address' => ['alice@example.test'],
			'underscore' => ['exa_mple.test'],
			'leading hyphen' => ['-example.test'],
			'label over 63 characters' => [str_repeat('b', 64) . '.test'],
			'mixed text directions' => ["a\u{05D0}b.example"],
			'zero width joiner' => ["a\u{200D}b.example"],
			'invalid IPv4 literal' => ['[300.1.1.1]'],
			'IPv6 address literal' => ['[IPv6:2001:db8::1]'],
			'IPv6 address literal as stored before' => ['ipv6:2001:db8::1'],
			'IPv6 literal without the tag' => ['[2001:db8::1]'],
			'domain in brackets' => ['[example.test]'],
			'all-numeric top-level domain' => ['example.123'],
			'numeric labels that are no IPv4 address' => ['999.1.1.1'],
			'single numeric label' => ['123'],
		];
	}

	/**
	 * @dataProvider provideInvalidDomains
	 */
	public function testRejectsInvalidDomain(string $domain): void {
		$this->expectException(InvalidAddressException::class);
		$this->expectExceptionMessage('Invalid domain');

		$this->normalizer->normalize($domain, AddressEntryType::Domain);
	}

	public function testTryNormalizeReturnsTheStoredForm(): void {
		$this->assertSame('bücher.example', $this->normalizer->tryNormalize('xn--bcher-kva.example', 'domain'));
	}

	public static function provideInvalidEntries(): array {
		return [
			'invalid domain' => ['not valid', 'domain'],
			'invalid address' => ['alice', 'individual'],
			'unknown type' => ['example.test', 'group'],
		];
	}

	/**
	 * @dataProvider provideInvalidEntries
	 */
	public function testTryNormalizeReturnsNullForAnInvalidEntry(string $value, string $type): void {
		$this->assertNull($this->normalizer->tryNormalize($value, $type));
	}

	public function testAcceptsAStoredFormOf254Octets(): void {
		$domain = self::internationalizedDomain(254);

		$normalized = $this->normalizer->normalize($domain, AddressEntryType::Domain);

		$this->assertSame($domain, $normalized);
	}

	public static function provideValuesOver254Octets(): array {
		return [
			// RFC 5321 limits a path to 256 octets including the angle brackets, which leaves 254
			'address of 255 octets' => [str_repeat('a', 242) . '@example.test', AddressEntryType::Individual],
			'domain of 255 octets' => [self::internationalizedDomain(255), AddressEntryType::Domain],
			// Each "．" (U+FF0E, 3 octets) becomes ".", so only the value as given is too long
			'domain that normalizing shortens' => [str_repeat("a\u{FF0E}", 85) . 'test', AddressEntryType::Domain],
		];
	}

	/**
	 * @dataProvider provideValuesOver254Octets
	 */
	public function testRejectsAValueOver254Octets(string $value, AddressEntryType $type): void {
		$this->expectException(InvalidAddressException::class);
		$this->expectExceptionMessage('Email address or domain too long');

		$this->normalizer->normalize($value, $type);
	}

	public static function provideValuesStoredWithOver254Octets(): array {
		// UTS #46 maps "㌀" (U+3300, 3 octets) to "アパート" (12 octets)
		$label = str_repeat("\u{3300}", 12);
		return [
			'domain of 118 octets that UTS #46 lengthens' => ["$label.$label.$label.example", AddressEntryType::Domain],
			'address of 124 octets that UTS #46 lengthens' => ["alice@$label.$label.$label.example", AddressEntryType::Individual],
		];
	}

	/**
	 * @dataProvider provideValuesStoredWithOver254Octets
	 */
	public function testRejectsAStoredFormOver254Octets(string $value, AddressEntryType $type): void {
		$this->expectException(InvalidAddressException::class);
		$this->expectExceptionMessage('Email address or domain too long');

		$this->normalizer->normalize($value, $type);
	}

	public static function provideSenders(): array {
		return [
			'IPv4 address literal' => ['bob@[192.168.0.1]'],
			'quoted local part' => ['"alice smith"@example.test'],
			'punycode domain' => ['alice@xn--bcher-kva.example'],
			'upper case' => ['ALICE@Example.TEST'],
			'zero width non-joiner' => ["\u{0639}\u{0644}\u{06CC}\u{200C}\u{0631}\u{0636}\u{0627}@example.test"],
		];
	}

	/**
	 * @dataProvider provideSenders
	 */
	public function testStoresTheFormSendersAreComparedWith(string $address): void {
		$sender = AddressList::parse($address)->first();

		$normalized = $this->normalizer->normalize($address, AddressEntryType::Individual);

		$this->assertSame($sender?->getEmail(), $normalized);
	}

	/**
	 * @dataProvider provideSenders
	 */
	public function testKeepsTheStoredFormOfASender(string $address): void {
		$stored = $this->normalizer->normalize($address, AddressEntryType::Individual);

		$normalized = $this->normalizer->normalize($stored, AddressEntryType::Individual);

		$this->assertSame($stored, $normalized);
	}

	private static function internationalizedDomain(int $octets): string {
		// 57 "ü" (2 octets each) are the most that fit a 63-character punycode label
		$label = str_repeat('ü', 57);
		$domain = "$label.$label.example";
		return str_repeat('a', $octets - strlen($domain) - 1) . ".$domain";
	}
}
