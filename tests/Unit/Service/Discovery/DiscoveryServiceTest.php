<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Discovery;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Generator;
use Horde_Mail_Rfc822_Address;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Dns\Resolver;
use OCA\Mail\IMAP\ImapDiscoveryConnector;
use OCA\Mail\JMAP\JmapDiscoveryConnector;
use OCA\Mail\Service\Discovery\AutoconfigLookup;
use OCA\Mail\Service\Discovery\DiscoveryContext;
use OCA\Mail\Service\Discovery\DiscoveryEvent;
use OCA\Mail\Service\Discovery\DiscoveryService;
use PHPUnit\Framework\MockObject\MockObject;
use function iterator_to_array;

class DiscoveryServiceTest extends TestCase {
	private AutoconfigLookup&MockObject $autoconfigLookup;
	private Resolver&MockObject $resolver;
	private ImapDiscoveryConnector&MockObject $imapConnector;
	private JmapDiscoveryConnector&MockObject $jmapConnector;
	private DiscoveryService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->autoconfigLookup = $this->createMock(AutoconfigLookup::class);
		$this->resolver = $this->createMock(Resolver::class);
		$this->imapConnector = $this->createMock(ImapDiscoveryConnector::class);
		$this->imapConnector->method('protocol')->willReturn(MailAccount::PROTOCOL_IMAP);
		$this->jmapConnector = $this->createMock(JmapDiscoveryConnector::class);
		$this->jmapConnector->method('protocol')->willReturn(MailAccount::PROTOCOL_JMAP);

		$this->service = new DiscoveryService(
			$this->autoconfigLookup,
			$this->resolver,
			$this->imapConnector,
			$this->jmapConnector,
		);
	}

	/**
	 * @param list<DiscoveryEvent> $events
	 */
	private static function generate(array $events): Generator {
		yield from $events;
	}

	/**
	 * @param list<DiscoveryEvent> $events
	 * @return list<string>
	 */
	private static function summarize(array $events): array {
		$summary = [];
		foreach ($events as $event) {
			$summary[] = $event->getProtocol() . ':' . $event->getType();
		}
		return $summary;
	}

	public function testAlternatesBetweenConnectors(): void {
		$imap = MailAccount::PROTOCOL_IMAP;
		$jmap = MailAccount::PROTOCOL_JMAP;
		$this->imapConnector->method('discover')->willReturn(self::generate([
			DiscoveryEvent::probe($imap, DiscoveryEvent::SOURCE_AUTOCONFIG, DiscoveryEvent::STATUS_RUNNING),
			DiscoveryEvent::candidate($imap, DiscoveryEvent::SOURCE_AUTOCONFIG, []),
			DiscoveryEvent::probe($imap, DiscoveryEvent::SOURCE_AUTOCONFIG, DiscoveryEvent::STATUS_FOUND),
		]));
		$this->jmapConnector->method('discover')->willReturn(self::generate([
			DiscoveryEvent::probe($jmap, DiscoveryEvent::SOURCE_AUTOCONFIG, DiscoveryEvent::STATUS_RUNNING),
		]));

		$events = iterator_to_array($this->service->discover(new Horde_Mail_Rfc822_Address('user@example.org')), false);

		self::assertSame([
			'imap:probe',
			'jmap:probe',
			'jmap:complete',
			'imap:candidate',
			'imap:probe',
			'imap:complete',
		], self::summarize($events));
		self::assertFalse($events[2]->jsonSerialize()['found']);
		self::assertTrue($events[5]->jsonSerialize()['found']);
	}

	public function testCompletesConnectorsWithoutEvents(): void {
		$this->imapConnector->method('discover')->willReturn(self::generate([]));
		$this->jmapConnector->method('discover')->willReturn(self::generate([]));

		$events = iterator_to_array($this->service->discover(new Horde_Mail_Rfc822_Address('user@example.org')), false);

		self::assertSame(['imap:complete', 'jmap:complete'], self::summarize($events));
	}

	public function testSharesOneContext(): void {
		$contexts = [];
		$this->imapConnector->method('discover')
			->willReturnCallback(function (DiscoveryContext $context) use (&$contexts): Generator {
				$contexts[] = $context;
				return self::generate([]);
			});
		$this->jmapConnector->method('discover')
			->willReturnCallback(function (DiscoveryContext $context) use (&$contexts): Generator {
				$contexts[] = $context;
				return self::generate([]);
			});

		iterator_to_array($this->service->discover(new Horde_Mail_Rfc822_Address('user@example.org')), false);

		self::assertCount(2, $contexts);
		self::assertSame($contexts[0], $contexts[1]);
		self::assertSame('user@example.org', $contexts[0]->getEmail());
	}
}
