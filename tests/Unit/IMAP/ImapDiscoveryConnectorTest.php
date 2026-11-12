<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Mail_Rfc822_Address;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Dns\Resolver;
use OCA\Mail\IMAP\ImapDiscoveryConnector;
use OCA\Mail\IMAP\PortProbe;
use OCA\Mail\Service\Discovery\AutoconfigLookup;
use OCA\Mail\Service\Discovery\AutoconfigResult;
use OCA\Mail\Service\Discovery\DiscoveryContext;
use OCA\Mail\Service\Discovery\DiscoveryEvent;
use OCA\Mail\Service\Discovery\ImapServerConfiguration;
use OCA\Mail\Service\Discovery\ServerEndpoint;
use OCP\Security\IRemoteHostValidator;
use PHPUnit\Framework\MockObject\MockObject;
use function array_key_last;
use function iterator_to_array;
use function json_decode;
use function json_encode;

class ImapDiscoveryConnectorTest extends TestCase {
	private AutoconfigLookup&MockObject $autoconfigLookup;
	private Resolver&MockObject $resolver;
	private PortProbe&MockObject $portProbe;
	private IRemoteHostValidator&MockObject $hostValidator;
	private DiscoveryContext $context;
	private ImapDiscoveryConnector $connector;

	/** @var array<string, list<array<string, mixed>>> */
	private array $dns = [];

	protected function setUp(): void {
		parent::setUp();

		$this->autoconfigLookup = $this->createMock(AutoconfigLookup::class);
		$this->resolver = $this->createMock(Resolver::class);
		$this->resolver->method('resolve')
			->willReturnCallback(fn (string $name): array => $this->dns[$name] ?? []);
		$this->portProbe = $this->createMock(PortProbe::class);
		$this->hostValidator = $this->createMock(IRemoteHostValidator::class);
		$this->hostValidator->method('isValid')->willReturn(true);

		$this->context = new DiscoveryContext(new Horde_Mail_Rfc822_Address('user@example.org'), $this->autoconfigLookup, $this->resolver);
		$this->connector = new ImapDiscoveryConnector($this->portProbe, $this->hostValidator);
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

	private static function server(string $host, int $port, string $security): array {
		return ['username' => 'user@example.org', 'host' => $host, 'port' => $port, 'security' => $security];
	}

	public function testProtocol(): void {
		self::assertSame(MailAccount::PROTOCOL_IMAP, $this->connector->protocol());
	}

	public function testAutoconfig(): void {
		$this->autoconfigLookup->method('lookup')->willReturn(new AutoconfigResult(
			new ImapServerConfiguration(
				new ServerEndpoint('user@example.org', 'imap.example.org', 993, 'ssl'),
				new ServerEndpoint('user@example.org', 'smtp.example.org', 465, 'ssl'),
			),
			null,
		));
		$this->dns = [
			'_imaps._tcp.example.org.' => [['pri' => 0, 'weight' => 1, 'port' => 993, 'target' => 'other.example.org']],
		];
		$this->portProbe->expects(self::never())->method('canConnect');

		$events = $this->discover();

		self::assertSame([
			['type' => 'probe', 'protocol' => 'imap', 'step' => 'autoconfig', 'status' => 'running'],
			[
				'type' => 'candidate',
				'protocol' => 'imap',
				'source' => 'autoconfig',
				'config' => [
					'imap' => [
						'incoming' => self::server('imap.example.org', 993, 'ssl'),
						'outgoing' => self::server('smtp.example.org', 465, 'ssl'),
					],
				],
			],
			['type' => 'probe', 'protocol' => 'imap', 'step' => 'autoconfig', 'status' => 'found'],
		], $events);
	}

	public function testSrvPrefersImplicitTls(): void {
		$this->dns = [
			'_imaps._tcp.example.org.' => [['pri' => 0, 'weight' => 1, 'port' => 993, 'target' => 'imaps.example.org']],
			'_imap._tcp.example.org.' => [['pri' => 0, 'weight' => 1, 'port' => 143, 'target' => 'imap.example.org']],
			'_submission._tcp.example.org.' => [['pri' => 0, 'weight' => 1, 'port' => 587, 'target' => 'smtp.example.org']],
		];

		$this->portProbe->expects(self::never())->method('canConnect');

		$events = $this->discover();

		$candidates = self::candidates($events);
		self::assertCount(1, $candidates);
		self::assertSame('srv', $candidates[0]['source']);
		self::assertSame(['type' => 'probe', 'protocol' => 'imap', 'step' => 'srv', 'status' => 'found'], $events[array_key_last($events)]);
		self::assertSame([
			'imap' => [
				'incoming' => self::server('imaps.example.org', 993, 'ssl'),
				'outgoing' => self::server('smtp.example.org', 587, 'tls'),
			],
		], $candidates[0]['config']);
	}

	public function testSrvWithoutSubmission(): void {
		$this->dns = [
			'_imap._tcp.example.org.' => [['pri' => 0, 'weight' => 1, 'port' => 143, 'target' => 'imap.example.org']],
		];

		$candidates = self::candidates($this->discover());

		self::assertCount(1, $candidates);
		self::assertSame(self::server('imap.example.org', 143, 'tls'), $candidates[0]['config']['imap']['incoming']);
		self::assertNull($candidates[0]['config']['imap']['outgoing']);
	}

	public function testGuessProbesHighestPriorityMx(): void {
		$this->dns = [
			'example.org.' => [
				['pri' => 20, 'target' => 'mx2.example.org'],
				['pri' => 10, 'target' => 'mx1.example.org'],
			],
		];
		$this->portProbe->method('canConnect')
			->willReturnCallback(fn (string $host, int $port): bool => $host === 'mx1.example.org' && ($port === 143 || $port === 587));

		$events = $this->discover();

		$candidates = self::candidates($events);
		self::assertCount(1, $candidates);
		self::assertSame('guess', $candidates[0]['source']);
		self::assertSame([
			'imap' => [
				'incoming' => self::server('mx1.example.org', 143, 'tls'),
				'outgoing' => self::server('mx1.example.org', 587, 'tls'),
			],
		], $candidates[0]['config']);
		self::assertSame(['type' => 'probe', 'protocol' => 'imap', 'step' => 'guess', 'status' => 'found'], $events[array_key_last($events)]);
	}

	public function testGuessSkipsSmtpWithoutImap(): void {
		$this->dns = [
			'example.org.' => [['pri' => 10, 'target' => 'mx.example.org']],
		];
		$this->portProbe->expects(self::exactly(2))
			->method('canConnect')
			->willReturnCallback(function (string $host, int $port): bool {
				self::assertContains($port, [993, 143]);
				return false;
			});

		$events = $this->discover();

		self::assertSame([], self::candidates($events));
		self::assertSame(['type' => 'probe', 'protocol' => 'imap', 'step' => 'guess', 'status' => 'none'], $events[array_key_last($events)]);
	}

	public function testGuessWithoutSmtp(): void {
		$this->dns = [
			'example.org.' => [['pri' => 10, 'target' => 'mx.example.org']],
		];
		$this->portProbe->method('canConnect')
			->willReturnCallback(fn (string $host, int $port): bool => $port === 993);

		$candidates = self::candidates($this->discover());

		self::assertCount(1, $candidates);
		self::assertSame(self::server('mx.example.org', 993, 'ssl'), $candidates[0]['config']['imap']['incoming']);
		self::assertNull($candidates[0]['config']['imap']['outgoing']);
	}

	public function testGuessRefusesInvalidHost(): void {
		$this->dns = [
			'example.org.' => [['pri' => 10, 'target' => 'localhost']],
		];
		$this->hostValidator = $this->createMock(IRemoteHostValidator::class);
		$this->hostValidator->method('isValid')->with('localhost')->willReturn(false);
		$this->connector = new ImapDiscoveryConnector($this->portProbe, $this->hostValidator);
		$this->portProbe->expects(self::never())->method('canConnect');

		$events = $this->discover();

		self::assertSame([], self::candidates($events));
	}

	public function testGuessWithoutMx(): void {
		$this->portProbe->expects(self::never())->method('canConnect');

		$events = $this->discover();

		self::assertSame([], self::candidates($events));
		self::assertSame(['type' => 'probe', 'protocol' => 'imap', 'step' => 'guess', 'status' => 'none'], $events[array_key_last($events)]);
	}
}
