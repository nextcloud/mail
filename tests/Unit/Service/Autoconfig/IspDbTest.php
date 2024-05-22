<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Service\Autoconfig;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use Horde_Mail_Rfc822_Address;
use InvalidArgumentException;
use OCA\Mail\Dns\Resolver;
use OCA\Mail\Service\AutoConfig\IspDb;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use function file_get_contents;
use function str_starts_with;

class IspDbTest extends TestCase {
	/** @var IClientService|MockObject */
	private $clientService;

	/** @var IClient|MockObject */
	private $client;

	/** @var MockObject|Resolver */
	private MockObject|Resolver $dnsResolver;

	/** @var LoggerInterface|MockObject */
	private $logger;

	private IspDb $ispDb;

	protected function setUp(): void {
		parent::setUp();

		$this->clientService = $this->createMock(IClientService::class);
		$this->client = $this->createMock(IClient::class);
		$this->dnsResolver = $this->createMock(Resolver::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->clientService->method('newClient')
			->willReturn($this->client);

		$this->ispDb = new IspDb(
			$this->clientService,
			$this->dnsResolver,
			$this->logger,
		);
	}

	public function fakeAutoconfigData() {
		return [
			['freenet.de', 'user@freenet.de', true],
			//['example.com', false], //should it fail?
		];
	}

	public function testQueryGmx(): void {
		$this->client->method('get')
			->willReturnCallback(function ($url) {
				switch ($url) {
					case 'https://autoconfig.gmx.com/mail/config-v1.1.xml?emailaddress=test@gmx.com':
					case 'http://autoconfig.gmx.com/mail/config-v1.1.xml?emailaddress=test@gmx.com':
						throw new \Exception('cURL error 6: Could not resolve host: autoconfig.gmx.com');
					case 'https://gmx.com/.well-known/autoconfig/mail/config-v1.1.xml?emailaddress=test@gmx.com':
					case 'http://gmx.com/.well-known/autoconfig/mail/config-v1.1.xml?emailaddress=test@gmx.com':
						throw new \Exception('Client error: `GET https://gmx.com/.well-known/autoconfig/mail/config-v1.1.xml?emailaddress=test@gmx.com` resulted in a `404 Not Found` response');
					case 'https://autoconfig.thunderbird.net/v1.1/gmx.com':
						$response = $this->createMock(IResponse::class);
						$response->method('getBody')->willReturn(file_get_contents(__DIR__ . '/../../../resources/autoconfig-gmx.xml'));
						return $response;
					default:
						throw new InvalidArgumentException();
				}
			});

		$email = new Horde_Mail_Rfc822_Address('test@gmx.com');
		$configuration = $this->ispDb->query('gmx.com', $email);

		self::assertNotNull($configuration);
		self::assertNotNull($configuration->getImapConfig());
		self::assertNotNull($configuration->getSmtpConfig());
	}

	public function testQueryOutlook(): void {
		$this->client->method('get')
			->willReturnCallback(function ($url) {
				switch ($url) {
					case 'https://autoconfig.outlook.com/mail/config-v1.1.xml?emailaddress=test@outlook.com':
					case 'http://autoconfig.outlook.com/mail/config-v1.1.xml?emailaddress=test@outlook.com':
						throw new \Exception('cURL error 6: Could not resolve host: autoconfig.outlook.com');
					case 'https://outlook.com/.well-known/autoconfig/mail/config-v1.1.xml?emailaddress=test@outlook.com':
					case 'http://outlook.com/.well-known/autoconfig/mail/config-v1.1.xml?emailaddress=test@outlook.com':
						throw new \Exception('Client error: `GET https://outlook.com/.well-known/autoconfig/mail/config-v1.1.xml?emailaddress=test@outlook.com` resulted in a `404 Not Found` response');
					case 'https://autoconfig.thunderbird.net/v1.1/outlook.com':
						$response = $this->createMock(IResponse::class);
						$response->method('getBody')->willReturn(file_get_contents(__DIR__ . '/../../../resources/autoconfig-outlook.xml'));
						return $response;
				}
			});

		$email = new Horde_Mail_Rfc822_Address('test@outlook.com');
		$configuration = $this->ispDb->query('outlook.com', $email);

		self::assertNotNull($configuration);
		self::assertNotNull($configuration->getImapConfig());
		self::assertNotNull($configuration->getSmtpConfig());
	}

	public function testQueryPosteo(): void {
		$this->client->method('get')
			->willReturnCallback(function () {
				$response = $this->createMock(IResponse::class);
				$response->method('getBody')->willReturn(file_get_contents(__DIR__ . '/../../../resources/autoconfig-posteo.xml'));
				return $response;
			});

		$email = new Horde_Mail_Rfc822_Address('test@postdeo.org');
		$configuration = $this->ispDb->query('posteo.org', $email);

		self::assertNotNull($configuration);
		self::assertNotNull($configuration->getImapConfig());
		self::assertNotNull($configuration->getSmtpConfig());
	}

	public function testQueryByMx(): void {
		$this->dnsResolver->expects(self::once())
			->method('resolve')
			->with('company.org', DNS_MX)
			->willReturn([
				[
					'target' => 'mx.company.org',
				]
			]);
		$this->dnsResolver->expects(self::once())
			->method('isSuffix')
			->with('company.org')
			->willReturn(false);
		$contactedServer = false;
		$this->client->method('get')
			->willReturnCallback(function ($url) use (&$contactedServer) {
				if (str_starts_with($url, 'https://autoconfig.company.org')) {
					$contactedServer = true;
				}
				throw new Exception('Random error');
			});

		$email = new Horde_Mail_Rfc822_Address('test@company.org');
		$configuration = $this->ispDb->query('company.org', $email);

		self::assertTrue($contactedServer, 'Should have contacted the server');
		self::assertNull($configuration);
	}

	public function testQueryByMxSameDomain(): void {
		$this->dnsResolver->expects(self::once())
			->method('resolve')
			->with('company.org', DNS_MX)
			->willReturn([
				[
					'target' => 'company.org',
				]
			]);
		$this->dnsResolver->expects(self::once())
			->method('isSuffix')
			->with('org')
			->willReturn(true);
		$contactedServer = false;
		$this->client->method('get')
			->willReturnCallback(function ($url) use (&$contactedServer) {
				if (str_starts_with($url, 'https://autoconfig.org')) {
					$contactedServer = true;
				}
				throw new Exception('Random error');
			});

		$email = new Horde_Mail_Rfc822_Address('test@company.org');
		$configuration = $this->ispDb->query('company.org', $email);

		self::assertFalse($contactedServer, 'Should not have contacted the server');
		self::assertNull($configuration);
	}
}
