<?php

declare(strict_types=1);

/**
 * @author Bernhard Scheirle <bernhard+git@scheirle.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Scheirle <bernhard+git@scheirle.de>
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

namespace OCA\Mail\Service\AutoConfig;

use Exception;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use Psr\Log\LoggerInterface;

class IspDb {

	/** @var string[] */
	private const SUPPORTED_TYPES = ['imap', 'smtp'];

	/** @var IClient */
	private $client;

	/** @var LoggerInterface */
	private $logger;
	private Resolver $dnsResolver;

	/** @returns string[] */
	public function getUrls(): array {
		return [
			'{SCHEME}://autoconfig.{DOMAIN}/mail/config-v1.1.xml?emailaddress={EMAIL}',
			'{SCHEME}://{DOMAIN}/.well-known/autoconfig/mail/config-v1.1.xml?emailaddress={EMAIL}',
			'https://autoconfig.thunderbird.net/v1.1/{DOMAIN}',
		];
	}

	public function __construct(IClientService $clientService,
		Resolver $dnsResolver,
		LoggerInterface $logger) {
		$this->client = $clientService->newClient();
		$this->dnsResolver = $dnsResolver;
		$this->logger = $logger;
	}

	/**
	 * Query IspDb for the given url
	 */
	private function queryUrl(string $url): array {
		try {
			$xml = $this->client->get($url, [
				'timeout' => 7,
			])->getBody();
		} catch (Exception $e) {
			$this->logger->debug('IsbDb: <' . $url . '> failed with "' . $e->getMessage() . '"', [
				'exception' => $e,
			]);
			return [];
		}

		$data = simplexml_load_string($xml);
		if ($data === false || !isset($data->emailProvider)) {
			return [];
		}

		$provider = [
			'displayName' => (string)$data->emailProvider->displayName,
			'imap' => [],
			'smtp' => [],
		];

		foreach ($data->emailProvider->incomingServer as $server) {
			$type = (string)$server['type'];
			if (in_array($type, self::SUPPORTED_TYPES)) {
				$provider[$type][] = $this->convertServerElement($server);
			}
		}

		foreach ($data->emailProvider->outgoingServer as $server) {
			$type = (string)$server['type'];
			if (in_array($type, self::SUPPORTED_TYPES)) {
				$provider[$type][] = $this->convertServerElement($server);
			}
		}

		return $provider;
	}

	/**
	 * Convert an incomingServer and outgoingServer xml element to array.
	 */
	private function convertServerElement(\SimpleXMLElement $server): array {
		return [
			'hostname' => (string)$server->hostname,
			'port' => (int)$server->port,
			'socketType' => (string)$server->socketType,
			'username' => (string)$server->username,
			'authentication' => (string)$server->authentication,
		];
	}

	/**
	 * @param string $domain
	 * @param bool $tryMx
	 * @return array
	 */
	public function query(string $domain, string $email, bool $tryMx = true): array {
		$this->logger->debug("IsbDb: querying <$domain>");
		if (strpos($domain, '@') !== false) {
			// TODO: use horde mail address parsing instead
			[, $domain] = explode('@', $domain);
		}

		$provider = [];
		foreach ($this->getUrls() as $url) {
			$url = str_replace(['{DOMAIN}', '{EMAIL}'], [$domain, $email], $url);
			if (strpos($url, '{SCHEME}') !== false) {
				foreach (['https', 'http'] as $scheme) {
					$completeurl = str_replace('{SCHEME}', $scheme, $url);
					$this->logger->debug("IsbDb: querying <$domain> via <$completeurl>");
					$provider = $this->queryUrl($completeurl);
					if (!empty($provider)) {
						return $provider;
					}
				}
			} else {
				$this->logger->debug("IsbDb: querying <$domain> via <$url>");
				$provider = $this->queryUrl($url);
				if (!empty($provider)) {
					return $provider;
				}
			}
		}

		if ($tryMx && ($dns = $this->dnsResolver->resolve($domain, DNS_MX))) {
			$domain = $dns[0]['target'];
			if (!($provider = $this->query($domain, $email, false))) {
				[, $domain] = explode('.', $domain, 2);
				$provider = $this->query($domain, $email, false);
			}
		}
		return $provider;
	}
}
