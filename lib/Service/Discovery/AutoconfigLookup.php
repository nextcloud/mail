<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Discovery;

use Exception;
use Horde_Mail_Rfc822_Address;
use OCA\Mail\Dns\Resolver;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Security\IRemoteHostValidator;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use function explode;
use function in_array;
use function is_string;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function parse_url;
use function rawurlencode;
use function simplexml_load_string;
use function str_replace;
use function strtolower;

/**
 * Looks up the Thunderbird autoconfig file of a domain
 *
 * Ref https://datatracker.ietf.org/doc/draft-ietf-mailmaint-autoconfig/
 */
class AutoconfigLookup {
	private const URLS = [
		'https://autoconfig.{DOMAIN}/mail/config-v1.1.xml?emailaddress={EMAIL}',
		'https://{DOMAIN}/.well-known/autoconfig/mail/config-v1.1.xml?emailaddress={EMAIL}',
		'https://autoconfig.thunderbird.net/v1.1/{DOMAIN}',
	];
	private const TIMEOUT = 5;

	private IClient $client;

	public function __construct(
		IClientService $clientService,
		private Resolver $resolver,
		private IRemoteHostValidator $hostValidator,
		private LoggerInterface $logger,
	) {
		$this->client = $clientService->newClient();
	}

	/**
	 * Query the address's domain, then the domain of its primary MX host
	 * and that host's parent domain
	 */
	public function lookup(DiscoveryContext $context): ?AutoconfigResult {
		$result = $this->query($context->getDomain(), $context->getAddress());
		if ($result !== null) {
			return $result;
		}

		$mx = $context->mx()[0] ?? null;
		if ($mx === null) {
			return null;
		}
		$domains = [strtolower($mx)];
		$parent = explode('.', $domains[0], 2)[1] ?? '';
		if ($parent !== '' && !$this->resolver->isSuffix($parent)) {
			$domains[] = $parent;
		}
		foreach ($domains as $domain) {
			if ($domain === $context->getDomain()) {
				continue;
			}
			$result = $this->query($domain, $context->getAddress());
			if ($result !== null) {
				return $result;
			}
		}
		return null;
	}

	private function query(string $domain, Horde_Mail_Rfc822_Address $address): ?AutoconfigResult {
		foreach (self::URLS as $template) {
			$url = str_replace(['{DOMAIN}', '{EMAIL}'], [$domain, rawurlencode($address->bare_address)], $template);
			$result = $this->fetch($url, $address);
			if ($result !== null) {
				return $result;
			}
		}
		return null;
	}

	private function fetch(string $url, Horde_Mail_Rfc822_Address $address): ?AutoconfigResult {
		$host = parse_url($url, PHP_URL_HOST);
		if (!is_string($host) || !$this->hostValidator->isValid($host)) {
			return null;
		}

		try {
			$body = $this->client->get($url, ['timeout' => self::TIMEOUT])->getBody();
		} catch (Exception $e) {
			$this->logger->debug('Discovery: autoconfig <' . $url . '> failed: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return null;
		}
		if (!is_string($body)) {
			return null;
		}

		return $this->parse($body, $address);
	}

	private function parse(string $xml, Horde_Mail_Rfc822_Address $address): ?AutoconfigResult {
		$previous = libxml_use_internal_errors(true);
		$data = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		if ($data === false || $data->emailProvider->count() === 0) {
			return null;
		}

		$imap = null;
		$jmap = null;
		foreach ($data->emailProvider->incomingServer as $server) {
			$type = (string)$server['type'];
			if ($type === 'imap' && $imap === null) {
				$imap = $this->convertServer($server, $address);
			} elseif ($type === 'jmap' && $jmap === null) {
				$jmap = $this->convertJmapServer($server, $address);
			}
		}
		$smtp = null;
		foreach ($data->emailProvider->outgoingServer as $server) {
			if ((string)$server['type'] === 'smtp') {
				$smtp = $this->convertServer($server, $address);
				break;
			}
		}

		if ($imap === null && $jmap === null) {
			return null;
		}
		return new AutoconfigResult($imap !== null ? new ImapServerConfiguration($imap, $smtp) : null, $jmap);
	}

	private function convertServer(SimpleXMLElement $server, Horde_Mail_Rfc822_Address $address): ServerEndpoint {
		$security = match (strtolower((string)$server->socketType)) {
			'ssl' => ServerEndpoint::SECURITY_SSL,
			'starttls', 'tls' => ServerEndpoint::SECURITY_STARTTLS,
			default => ServerEndpoint::SECURITY_NONE,
		};
		return new ServerEndpoint(
			$this->expandUsername((string)$server->username, $address),
			(string)$server->hostname,
			(int)$server->port,
			$security,
		);
	}

	private function convertJmapServer(SimpleXMLElement $server, Horde_Mail_Rfc822_Address $address): ?JmapServerConfiguration {
		$authentication = [];
		foreach ($server->authentication as $method) {
			$mapped = match (strtolower((string)$method)) {
				'password-cleartext', 'http-basic' => JmapServerConfiguration::AUTH_PASSWORD,
				'oauth2' => JmapServerConfiguration::AUTH_OAUTH2,
				default => null,
			};
			if ($mapped !== null && !in_array($mapped, $authentication, true)) {
				$authentication[] = $mapped;
			}
		}

		return JmapServerConfiguration::fromUrl(
			(string)$server->url,
			$this->expandUsername((string)$server->username, $address),
			$authentication,
		);
	}

	private function expandUsername(string $username, Horde_Mail_Rfc822_Address $address): string {
		return str_replace(
			['%EMAILADDRESS%', '%EMAILLOCALPART%', '%EMAILDOMAIN%'],
			[$address->bare_address, $address->mailbox, $address->host],
			$username,
		);
	}
}
