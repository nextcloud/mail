<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

use Generator;
use OCA\Mail\Contracts\IDiscoveryConnector;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\Discovery\DiscoveryContext;
use OCA\Mail\Service\Discovery\DiscoveryEvent;
use OCA\Mail\Service\Discovery\ImapServerConfiguration;
use OCA\Mail\Service\Discovery\ServerEndpoint;
use OCP\Security\IRemoteHostValidator;
use Override;

/**
 * Discovers the IMAP and SMTP servers of an address, stopping at the first step that finds them
 */
class ImapDiscoveryConnector implements IDiscoveryConnector {
	/** RFC 6186 and RFC 8314 service names, implicit TLS first */
	private const IMAP_SERVICES = [
		'_imaps._tcp' => ServerEndpoint::SECURITY_SSL,
		'_imap._tcp' => ServerEndpoint::SECURITY_STARTTLS,
	];
	private const SMTP_SERVICES = [
		'_submissions._tcp' => ServerEndpoint::SECURITY_SSL,
		'_submission._tcp' => ServerEndpoint::SECURITY_STARTTLS,
	];

	private const IMAP_PORTS = [
		993 => ServerEndpoint::SECURITY_SSL,
		143 => ServerEndpoint::SECURITY_STARTTLS,
	];
	private const SMTP_PORTS = [
		465 => ServerEndpoint::SECURITY_SSL,
		587 => ServerEndpoint::SECURITY_STARTTLS,
	];

	public function __construct(
		private PortProbe $portProbe,
		private IRemoteHostValidator $hostValidator,
	) {
	}

	#[Override]
	public function protocol(): string {
		return MailAccount::PROTOCOL_IMAP;
	}

	#[Override]
	public function discover(DiscoveryContext $context): Generator {
		yield DiscoveryEvent::probe($this->protocol(), DiscoveryEvent::SOURCE_AUTOCONFIG, DiscoveryEvent::STATUS_RUNNING);
		$imap = $context->autoconfig()?->getImap();
		yield from $this->report(DiscoveryEvent::SOURCE_AUTOCONFIG, $imap);
		if ($imap !== null) {
			return;
		}

		yield DiscoveryEvent::probe($this->protocol(), DiscoveryEvent::SOURCE_SRV, DiscoveryEvent::STATUS_RUNNING);
		$imap = $this->lookupServices($context);
		yield from $this->report(DiscoveryEvent::SOURCE_SRV, $imap);
		if ($imap !== null) {
			return;
		}

		yield DiscoveryEvent::probe($this->protocol(), DiscoveryEvent::SOURCE_GUESS, DiscoveryEvent::STATUS_RUNNING);
		yield from $this->report(DiscoveryEvent::SOURCE_GUESS, $this->guess($context));
	}

	/**
	 * @param DiscoveryEvent::SOURCE_* $source
	 * @return Generator<int, DiscoveryEvent, mixed, void>
	 */
	private function report(string $source, ?ImapServerConfiguration $imap): Generator {
		if ($imap !== null) {
			yield DiscoveryEvent::candidate($this->protocol(), $source, ['imap' => $imap]);
		}
		yield DiscoveryEvent::probe($this->protocol(), $source, $imap !== null ? DiscoveryEvent::STATUS_FOUND : DiscoveryEvent::STATUS_NONE);
	}

	private function lookupServices(DiscoveryContext $context): ?ImapServerConfiguration {
		$incoming = $this->lookupService($context, self::IMAP_SERVICES);
		if ($incoming === null) {
			return null;
		}
		return new ImapServerConfiguration($incoming, $this->lookupService($context, self::SMTP_SERVICES));
	}

	/**
	 * @param array<string, ServerEndpoint::SECURITY_*> $services service name => security
	 */
	private function lookupService(DiscoveryContext $context, array $services): ?ServerEndpoint {
		foreach ($services as $service => $security) {
			$record = $context->srv($service)[0] ?? null;
			if ($record !== null) {
				return new ServerEndpoint($context->getEmail(), $record['target'], $record['port'], $security);
			}
		}
		return null;
	}

	/**
	 * Probe the standard ports of the primary MX host
	 */
	private function guess(DiscoveryContext $context): ?ImapServerConfiguration {
		$host = $context->mx()[0] ?? null;
		if ($host === null || !$this->hostValidator->isValid($host)) {
			return null;
		}

		$incoming = null;
		foreach (self::IMAP_PORTS as $port => $security) {
			if ($this->portProbe->canConnect($host, $port)) {
				$incoming = new ServerEndpoint($context->getEmail(), $host, $port, $security);
				break;
			}
		}
		if ($incoming === null) {
			return null;
		}

		$outgoing = null;
		foreach (self::SMTP_PORTS as $port => $security) {
			if ($this->portProbe->canConnect($host, $port)) {
				$outgoing = new ServerEndpoint($context->getEmail(), $host, $port, $security);
				break;
			}
		}
		return new ImapServerConfiguration($incoming, $outgoing);
	}
}
