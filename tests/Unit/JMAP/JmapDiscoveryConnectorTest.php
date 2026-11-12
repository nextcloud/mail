<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\JMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Mail_Rfc822_Address;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Dns\Resolver;
use OCA\Mail\JMAP\JmapDiscoveryConnector;
use OCA\Mail\JMAP\JmapSessionProbe;
use OCA\Mail\Service\Discovery\AutoconfigLookup;
use OCA\Mail\Service\Discovery\AutoconfigResult;
use OCA\Mail\Service\Discovery\DiscoveryContext;
use OCA\Mail\Service\Discovery\DiscoveryEvent;
use OCA\Mail\Service\Discovery\JmapServerConfiguration;
use PHPUnit\Framework\MockObject\MockObject;
use function array_key_last;
use function iterator_to_array;
use function json_decode;
use function json_encode;

class JmapDiscoveryConnectorTest extends TestCase {
	private AutoconfigLookup&MockObject $autoconfigLookup;
	private Resolver&MockObject $resolver;
	private JmapSessionProbe&MockObject $probe;
	private DiscoveryContext $context;
	private JmapDiscoveryConnector $connector;

	/** @var array<string, list<array<string, mixed>>> */
	private array $dns = [];

	protected function setUp(): void {
		parent::setUp();

		$this->autoconfigLookup = $this->createMock(AutoconfigLookup::class);
		$this->resolver = $this->createMock(Resolver::class);
		$this->resolver->method('resolve')
			->willReturnCallback(fn (string $name): array => $this->dns[$name] ?? []);
		$this->probe = $this->createMock(JmapSessionProbe::class);

		$this->context = new DiscoveryContext(new Horde_Mail_Rfc822_Address('user@example.org'), $this->autoconfigLookup, $this->resolver);
		$this->connector = new JmapDiscoveryConnector($this->probe);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function discover(): array {
		$events = iterator_to_array($this->connector->discover($this->context), false);
		return json_decode(json_encode($events, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @param list<array<string, mixed>> $events
	 * @return list<array<string, mixed>>
	 */
	private static function candidates(array $events): array {
		$candidates = [];
		foreach ($events as $event) {
			if ($event['type'] === DiscoveryEvent::TYPE_CANDIDATE) {
				$candidates[] = $event;
			}
		}
		return $candidates;
	}

	public function testProtocol(): void {
		self::assertSame(MailAccount::PROTOCOL_JMAP, $this->connector->protocol());
	}

	public function testAutoconfig(): void {
		$this->autoconfigLookup->method('lookup')->willReturn(new AutoconfigResult(
			null,
			JmapServerConfiguration::fromUrl('https://jmap.example.org/session', 'user', [JmapServerConfiguration::AUTH_PASSWORD]),
		));
		$this->dns = [
			'_jmap._tcp.example.org.' => [['pri' => 0, 'weight' => 1, 'port' => 443, 'target' => 'api.example.org']],
		];
		$this->probe->expects(self::never())->method('probe');

		$events = $this->discover();

		self::assertSame([
			['type' => 'probe', 'protocol' => 'jmap', 'step' => 'autoconfig', 'status' => 'running'],
			[
				'type' => 'candidate',
				'protocol' => 'jmap',
				'source' => 'autoconfig',
				'config' => [
					'jmap' => [
						'endpoint' => ['username' => 'user', 'host' => 'jmap.example.org', 'port' => 443, 'security' => 'ssl'],
						'path' => '/session',
						'authentication' => ['password'],
					],
				],
			],
			['type' => 'probe', 'protocol' => 'jmap', 'step' => 'autoconfig', 'status' => 'found'],
		], $events);
	}

	public function testSrvOnDefaultPort(): void {
		$this->dns = [
			'_jmap._tcp.example.org.' => [['pri' => 0, 'weight' => 1, 'port' => 443, 'target' => 'api.example.org']],
		];
		$this->probe->expects(self::once())
			->method('probe')
			->with('https://api.example.org/.well-known/jmap', 'user@example.org')
			->willReturn(JmapServerConfiguration::fromUrl('https://api.example.org/jmap/session', 'user@example.org', [JmapServerConfiguration::AUTH_OAUTH2]));

		$events = $this->discover();

		$candidates = self::candidates($events);
		self::assertCount(1, $candidates);
		self::assertSame('srv', $candidates[0]['source']);
		self::assertSame(['type' => 'probe', 'protocol' => 'jmap', 'step' => 'srv', 'status' => 'found'], $events[array_key_last($events)]);
		self::assertSame('api.example.org', $candidates[0]['config']['jmap']['endpoint']['host']);
		self::assertSame('/jmap/session', $candidates[0]['config']['jmap']['path']);
		self::assertSame(['oauth2'], $candidates[0]['config']['jmap']['authentication']);
	}

	public function testSrvOnCustomPort(): void {
		$this->dns = [
			'_jmap._tcp.example.org.' => [['pri' => 0, 'weight' => 1, 'port' => 8443, 'target' => 'api.example.org']],
		];
		$probed = [];
		$this->probe->method('probe')
			->willReturnCallback(function (string $url) use (&$probed): ?JmapServerConfiguration {
				$probed[] = $url;
				return null;
			});

		$events = $this->discover();

		self::assertSame([
			'https://api.example.org:8443/.well-known/jmap',
			'https://example.org/.well-known/jmap',
		], $probed);
		self::assertContains(['type' => 'probe', 'protocol' => 'jmap', 'step' => 'srv', 'status' => 'none'], $events);
	}

	public function testWellKnownFallback(): void {
		$this->probe->expects(self::once())
			->method('probe')
			->with('https://example.org/.well-known/jmap', 'user@example.org')
			->willReturn(JmapServerConfiguration::fromUrl('https://example.org/.well-known/jmap', 'user@example.org', []));

		$events = $this->discover();

		$candidates = self::candidates($events);
		self::assertCount(1, $candidates);
		self::assertSame('well-known', $candidates[0]['source']);
		self::assertSame(['type' => 'probe', 'protocol' => 'jmap', 'step' => 'well-known', 'status' => 'found'], $events[array_key_last($events)]);
	}

	public function testNothingFound(): void {
		$this->probe->method('probe')->willReturn(null);

		$events = $this->discover();

		self::assertSame([], self::candidates($events));
		self::assertSame(['type' => 'probe', 'protocol' => 'jmap', 'step' => 'well-known', 'status' => 'none'], $events[array_key_last($events)]);
	}
}
