<?php

declare(strict_types=1);

/**
 * @author Bernhard Scheirle <bernhard+git@scheirle.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Tests\Unit\Service\Autoconfig;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Mail_Rfc822_Address;
use OCA\Mail\Service\AutoConfig\IspDb;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class IspDbTest extends TestCase {
	/** @var IClientService|MockObject */
	private $clientService;

	/** @var IClient|MockObject */
	private $client;

	/** @var LoggerInterface|MockObject */
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->clientService = $this->createMock(IClientService::class);
		$this->client = $this->createMock(IClient::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->clientService->method('newClient')
			->willReturn($this->client);
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
				}
			});

		$ispDb = new IspDb($this->clientService, $this->logger);

		$email = new Horde_Mail_Rfc822_Address('test@gmx.com');
		$configuration = $ispDb->query('gmx.com', $email);

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

		$ispDb = new IspDb($this->clientService, $this->logger);

		$email = new Horde_Mail_Rfc822_Address('test@outlook.com');
		$configuration = $ispDb->query('outlook.com', $email);

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

		$ispDb = new IspDb($this->clientService, $this->logger);

		$email = new Horde_Mail_Rfc822_Address('test@postdeo.org');
		$configuration = $ispDb->query('posteo.org', $email);

		self::assertNotNull($configuration);
		self::assertNotNull($configuration->getImapConfig());
		self::assertNotNull($configuration->getSmtpConfig());
	}
}
