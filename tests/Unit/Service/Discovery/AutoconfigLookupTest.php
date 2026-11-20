<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Discovery;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use Horde_Mail_Rfc822_Address;
use OCA\Mail\Dns\Resolver;
use OCA\Mail\Service\Discovery\AutoconfigLookup;
use OCA\Mail\Service\Discovery\DiscoveryContext;
use OCA\Mail\Service\Discovery\ImapServerConfiguration;
use OCA\Mail\Service\Discovery\JmapServerConfiguration;
use OCA\Mail\Service\Discovery\ServerEndpoint;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\Security\IRemoteHostValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use function file_get_contents;

class AutoconfigLookupTest extends TestCase {
	private Resolver&MockObject $resolver;
	private IRemoteHostValidator&MockObject $hostValidator;
	private AutoconfigLookup $lookup;

	/** @var array<string, string|Exception> */
	private array $responses = [];

	/** @var list<string> */
	private array $requested = [];

	/** @var list<array<string, mixed>> */
	private array $mx = [];

	protected function setUp(): void {
		parent::setUp();

		$client = $this->createMock(IClient::class);
		$client->method('get')
			->willReturnCallback(function (string $url): IResponse {
				$this->requested[] = $url;
				$body = $this->responses[$url] ?? new Exception('404 Not Found');
				if ($body instanceof Exception) {
					throw $body;
				}
				$response = $this->createMock(IResponse::class);
				$response->method('getBody')->willReturn($body);
				return $response;
			});
		$clientService = $this->createMock(IClientService::class);
		$clientService->method('newClient')->willReturn($client);
		$this->resolver = $this->createMock(Resolver::class);
		$this->resolver->method('resolve')
			->willReturnCallback(fn (): array => $this->mx);
		$this->hostValidator = $this->createMock(IRemoteHostValidator::class);
		$this->hostValidator->method('isValid')->willReturn(true);

		$this->lookup = new AutoconfigLookup(
			$clientService,
			$this->resolver,
			$this->hostValidator,
			$this->createMock(LoggerInterface::class),
		);
	}

	private function context(string $email = 'user@example.org'): DiscoveryContext {
		return new DiscoveryContext(new Horde_Mail_Rfc822_Address($email), $this->lookup, $this->resolver);
	}

	private static function fixture(string $name): string {
		return file_get_contents(__DIR__ . '/../../../resources/' . $name);
	}

	public function testParsesImapAndSmtp(): void {
		$this->responses = [
			'https://autoconfig.thunderbird.net/v1.1/posteo.de' => self::fixture('autoconfig-posteo.xml'),
		];

		$result = $this->lookup->lookup($this->context('user@posteo.de'));

		self::assertNotNull($result);
		self::assertEquals(new ImapServerConfiguration(
			new ServerEndpoint('user@posteo.de', 'posteo.de', 993, ServerEndpoint::SECURITY_SSL),
			new ServerEndpoint('user@posteo.de', 'posteo.de', 465, ServerEndpoint::SECURITY_SSL),
		), $result->getImap());
		self::assertNull($result->getJmap());
		self::assertSame([
			'https://autoconfig.posteo.de/mail/config-v1.1.xml?emailaddress=user%40posteo.de',
			'https://posteo.de/.well-known/autoconfig/mail/config-v1.1.xml?emailaddress=user%40posteo.de',
			'https://autoconfig.thunderbird.net/v1.1/posteo.de',
		], $this->requested);
	}

	public function testParsesJmap(): void {
		$this->responses = [
			'https://autoconfig.example.org/mail/config-v1.1.xml?emailaddress=user%40example.org' => self::fixture('autoconfig-jmap.xml'),
		];

		$result = $this->lookup->lookup($this->context());

		self::assertNotNull($result);
		self::assertNull($result->getImap());
		self::assertEquals(new JmapServerConfiguration(
			new ServerEndpoint('user', 'jmap.example.org', 443, ServerEndpoint::SECURITY_SSL),
			JmapServerConfiguration::DEFAULT_PATH,
			[JmapServerConfiguration::AUTH_OAUTH2, JmapServerConfiguration::AUTH_PASSWORD],
		), $result->getJmap());
		self::assertCount(1, $this->requested);
	}

	public function testMapsSocketTypes(): void {
		$this->responses = [
			'https://autoconfig.example.org/mail/config-v1.1.xml?emailaddress=user%40example.org' => <<<XML
				<clientConfig version="1.1"><emailProvider id="example.org">
				<incomingServer type="imap"><hostname>imap.example.org</hostname><port>143</port><socketType>STARTTLS</socketType><username>%EMAILADDRESS%</username></incomingServer>
				<outgoingServer type="smtp"><hostname>smtp.example.org</hostname><port>25</port><socketType>plain</socketType><username>%EMAILDOMAIN%</username></outgoingServer>
				</emailProvider></clientConfig>
				XML,
		];

		$result = $this->lookup->lookup($this->context());

		self::assertSame(ServerEndpoint::SECURITY_STARTTLS, $result?->getImap()?->getIncoming()->getSecurity());
		self::assertSame(ServerEndpoint::SECURITY_NONE, $result?->getImap()?->getOutgoing()?->getSecurity());
		self::assertSame('example.org', $result?->getImap()?->getOutgoing()?->getUsername());
	}

	public function testIgnoresInvalidXml(): void {
		$this->responses = [
			'https://autoconfig.example.org/mail/config-v1.1.xml?emailaddress=user%40example.org' => '<html><body>Not found',
		];

		self::assertNull($this->lookup->lookup($this->context()));
	}

	public function testIgnoresConfigurationWithoutImapOrJmap(): void {
		$this->responses = [
			'https://autoconfig.example.org/mail/config-v1.1.xml?emailaddress=user%40example.org' => <<<XML
				<clientConfig version="1.1"><emailProvider id="example.org">
				<incomingServer type="pop3"><hostname>pop.example.org</hostname><port>995</port><socketType>SSL</socketType></incomingServer>
				<incomingServer type="jmap"><username>%EMAILADDRESS%</username></incomingServer>
				<outgoingServer type="smtp"><hostname>smtp.example.org</hostname><port>465</port><socketType>SSL</socketType></outgoingServer>
				</emailProvider></clientConfig>
				XML,
		];

		self::assertNull($this->lookup->lookup($this->context()));
	}

	public function testFallsBackToMxDomains(): void {
		$this->mx = [['pri' => 10, 'target' => 'mx1.mailhost.net']];
		$this->resolver->method('isSuffix')->with('mailhost.net')->willReturn(false);
		$this->responses = [
			'https://autoconfig.mailhost.net/mail/config-v1.1.xml?emailaddress=user%40example.org' => self::fixture('autoconfig-posteo.xml'),
		];

		$result = $this->lookup->lookup($this->context());

		self::assertNotNull($result?->getImap());
		self::assertSame([
			'https://autoconfig.example.org/mail/config-v1.1.xml?emailaddress=user%40example.org',
			'https://example.org/.well-known/autoconfig/mail/config-v1.1.xml?emailaddress=user%40example.org',
			'https://autoconfig.thunderbird.net/v1.1/example.org',
			'https://autoconfig.mx1.mailhost.net/mail/config-v1.1.xml?emailaddress=user%40example.org',
			'https://mx1.mailhost.net/.well-known/autoconfig/mail/config-v1.1.xml?emailaddress=user%40example.org',
			'https://autoconfig.thunderbird.net/v1.1/mx1.mailhost.net',
			'https://autoconfig.mailhost.net/mail/config-v1.1.xml?emailaddress=user%40example.org',
		], $this->requested);
	}

	public function testDoesNotQueryPublicSuffixOfMx(): void {
		$this->mx = [['pri' => 10, 'target' => 'example.org']];
		$this->resolver->expects(self::once())->method('isSuffix')->with('org')->willReturn(true);

		self::assertNull($this->lookup->lookup($this->context()));
		self::assertCount(3, $this->requested);
	}

	public function testWithoutMx(): void {
		$this->resolver->expects(self::never())->method('isSuffix');

		self::assertNull($this->lookup->lookup($this->context()));
		self::assertCount(3, $this->requested);
	}

	public function testSkipsInvalidHosts(): void {
		$this->hostValidator = $this->createMock(IRemoteHostValidator::class);
		$this->hostValidator->method('isValid')
			->willReturnCallback(static fn (string $host): bool => $host === 'autoconfig.thunderbird.net');
		$client = $this->createMock(IClient::class);
		$client->expects(self::once())
			->method('get')
			->with('https://autoconfig.thunderbird.net/v1.1/example.org')
			->willThrowException(new Exception('404 Not Found'));
		$this->lookup = new AutoconfigLookup(
			$this->createConfiguredMock(IClientService::class, ['newClient' => $client]),
			$this->resolver,
			$this->hostValidator,
			$this->createMock(LoggerInterface::class),
		);

		self::assertNull($this->lookup->lookup($this->context()));
	}
}
