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
use Horde_Mail_Rfc822_Address;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use function str_replace;
use function strtolower;

class IspDb {
	/** @var IClient */
	private $client;

	/** @var LoggerInterface */
	private $logger;

	/** @returns string[] */
	public function getUrls(): array {
		return [
			'https://autoconfig.{DOMAIN}/mail/config-v1.1.xml?emailaddress={EMAIL}',
			'https://{DOMAIN}/.well-known/autoconfig/mail/config-v1.1.xml?emailaddress={EMAIL}',
			'https://autoconfig.thunderbird.net/v1.1/{DOMAIN}',
			'http://autoconfig.{DOMAIN}/mail/config-v1.1.xml?emailaddress={EMAIL}', // insecure fallback 1
			'http://{DOMAIN}/.well-known/autoconfig/mail/config-v1.1.xml?emailaddress={EMAIL}', // insecure fallback 2
		];
	}

	public function __construct(IClientService $clientService, LoggerInterface $logger) {
		$this->client = $clientService->newClient();
		$this->logger = $logger;
	}

	/**
	 * Query IspDb for the given url
	 */
	private function queryUrl(string $url, Horde_Mail_Rfc822_Address $email): ?Configuration {
		try {
			$xml = $this->client->get($url, [
				'timeout' => 7,
			])->getBody();
		} catch (Exception $e) {
			$this->logger->debug('IsbDb: <' . $url . '> failed with "' . $e->getMessage() . '"', [
				'exception' => $e,
			]);
			return null;
		}

		libxml_use_internal_errors(true);
		$data = simplexml_load_string($xml);

		if ($data === false || !isset($data->emailProvider)) {
			$errors = libxml_get_errors();
			foreach ($errors as $error) {
				$this->logger->debug("ISP DB returned an erroneous XML: " . $error->message);
			}
			return null;
		}

		$serverConfigs = [
			'imap' => [],
			'smtp' => [],
		];

		foreach ($data->emailProvider->incomingServer as $server) {
			$type = (string)$server['type'];
			if ($type === 'imap') {
				$serverConfigs[$type][] = $this->convertServerElement($server, $email);
			}
		}
		foreach ($data->emailProvider->outgoingServer as $server) {
			$type = (string)$server['type'];
			if ($type === 'smtp') {
				$serverConfigs[$type][] = $this->convertServerElement($server, $email);
			}
		}

		if (empty($serverConfigs['imap']) && empty($serverConfigs['smtp'])) {
			return null;
		}
		return new Configuration(
			$serverConfigs['imap'][0] ?? null,
			$serverConfigs['smtp'][0] ?? null,
		);
	}

	private function mapSocketTypeToSecurity(string $type): string {
		$lowerType = strtolower($type);
		if ($lowerType === 'ssl') {
			return 'ssl';
		}
		if ($lowerType === 'starttls' || $lowerType === 'tls') {
			return 'tls';
		}
		return 'none';
	}

	/**
	 * Convert an incomingServer or outgoingServer xml element
	 *
	 * Ref https://wiki.mozilla.org/Thunderbird:Autoconfiguration:ConfigFileFormat
	 */
	private function convertServerElement(SimpleXMLElement $server, Horde_Mail_Rfc822_Address $email): ServerConfiguration {
		return new ServerConfiguration(
			str_replace(
				[
					'%EMAILADDRESS%',
					'%EMAILLOCALPART%',
					'%EMAILDOMAIN%',
				],
				[
					$email->bare_address,
					$email->personal,
					$email->host,
				],
				(string)$server->username
			),
			(string)$server->hostname,
			(int)$server->port,
			$this->mapSocketTypeToSecurity((string)$server->socketType),
		);
	}

	public function query(string $domain, Horde_Mail_Rfc822_Address $email, bool $tryMx = true): ?Configuration {
		$this->logger->debug("IsbDb: querying <$domain>");

		$config = null;
		foreach ($this->getUrls() as $url) {
			$url = str_replace(['{DOMAIN}', '{EMAIL}'], [$domain, $email->bare_address], $url);
			$this->logger->debug("IsbDb: querying <$domain> via <$url>");
			$config = $this->queryUrl($url, $email);
			if ($config !== null) {
				return $config;
			}
		}

		if ($tryMx && ($dns = dns_get_record($domain, DNS_MX))) {
			$domain = $dns[0]['target'];
			if (!($config = $this->query($domain, $email, false))) {
				[, $domain] = explode('.', $domain, 2);
				$config = $this->query($domain, $email, false);
			}
		}
		return $config;
	}
}
