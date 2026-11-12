<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Discovery;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Mail_Rfc822_Address;
use OCA\Mail\Dns\Resolver;
use OCA\Mail\Service\Discovery\AutoconfigLookup;
use OCA\Mail\Service\Discovery\AutoconfigResult;
use OCA\Mail\Service\Discovery\DiscoveryContext;
use PHPUnit\Framework\MockObject\MockObject;

class DiscoveryContextTest extends TestCase {
	private Horde_Mail_Rfc822_Address $address;
	private AutoconfigLookup&MockObject $autoconfigLookup;
	private Resolver&MockObject $resolver;
	private DiscoveryContext $context;

	protected function setUp(): void {
		parent::setUp();

		$this->address = new Horde_Mail_Rfc822_Address('User@Example.ORG');
		$this->autoconfigLookup = $this->createMock(AutoconfigLookup::class);
		$this->resolver = $this->createMock(Resolver::class);

		$this->context = new DiscoveryContext($this->address, $this->autoconfigLookup, $this->resolver);
	}

	public function testDomainIsLowercase(): void {
		self::assertSame('example.org', $this->context->getDomain());
	}

	public function testAutoconfigIsQueriedOnce(): void {
		$configuration = new AutoconfigResult(null, null);
		$this->autoconfigLookup->expects(self::once())
			->method('lookup')
			->with($this->context)
			->willReturn($configuration);

		self::assertSame($configuration, $this->context->autoconfig());
		self::assertSame($configuration, $this->context->autoconfig());
	}

	public function testMissingAutoconfigIsQueriedOnce(): void {
		$this->autoconfigLookup->expects(self::once())
			->method('lookup')
			->willReturn(null);

		self::assertNull($this->context->autoconfig());
		self::assertNull($this->context->autoconfig());
	}

	public function testMxIsSortedByPriority(): void {
		$this->resolver->expects(self::once())
			->method('resolve')
			->with('example.org.', DNS_MX)
			->willReturn([
				['pri' => 20, 'target' => 'mx2.example.org'],
				['pri' => 10, 'target' => 'mx1.example.org'],
				['pri' => 30, 'target' => ''],
			]);

		self::assertSame(['mx1.example.org', 'mx2.example.org'], $this->context->mx());
		self::assertSame(['mx1.example.org', 'mx2.example.org'], $this->context->mx());
	}

	public function testMxLookupFailure(): void {
		$this->resolver->method('resolve')->willReturn(false);

		self::assertSame([], $this->context->mx());
	}

	public function testSrvIsSortedAndFiltered(): void {
		$this->resolver->expects(self::once())
			->method('resolve')
			->with('_imaps._tcp.example.org.', DNS_SRV)
			->willReturn([
				['pri' => 10, 'weight' => 1, 'port' => 993, 'target' => 'backup.example.org'],
				['pri' => 0, 'weight' => 1, 'port' => 993, 'target' => 'light.example.org'],
				['pri' => 0, 'weight' => 5, 'port' => 993, 'target' => 'heavy.example.org'],
				['pri' => 0, 'weight' => 9, 'port' => 0, 'target' => '.'],
			]);

		$records = $this->context->srv('_imaps._tcp');

		self::assertSame([
			['target' => 'heavy.example.org', 'port' => 993, 'priority' => 0, 'weight' => 5],
			['target' => 'light.example.org', 'port' => 993, 'priority' => 0, 'weight' => 1],
			['target' => 'backup.example.org', 'port' => 993, 'priority' => 10, 'weight' => 1],
		], $records);
		self::assertSame($records, $this->context->srv('_imaps._tcp'));
	}

	public function testSrvLookupFailure(): void {
		$this->resolver->method('resolve')->willReturn(false);

		self::assertSame([], $this->context->srv('_imaps._tcp'));
	}
}
