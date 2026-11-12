<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Discovery;

use Horde_Mail_Rfc822_Address;
use OCA\Mail\Dns\Resolver;
use function strtolower;
use function usort;

/**
 * Lookups for one address, cached so that connectors can share them
 *
 * @psalm-type SrvRecord = array{target: string, port: int, priority: int, weight: int}
 */
final class DiscoveryContext {
	private bool $autoconfigLoaded = false;
	private ?AutoconfigResult $autoconfig = null;
	/** @var list<string>|null */
	private ?array $mx = null;
	/** @var array<string, list<SrvRecord>> */
	private array $srv = [];

	public function __construct(
		private Horde_Mail_Rfc822_Address $address,
		private AutoconfigLookup $autoconfigLookup,
		private Resolver $resolver,
	) {
	}

	public function getAddress(): Horde_Mail_Rfc822_Address {
		return $this->address;
	}

	public function getEmail(): string {
		return $this->address->bare_address;
	}

	public function getDomain(): string {
		return strtolower($this->address->host);
	}

	/**
	 * The trailing dot keeps the resolver from also trying the host's DNS
	 * search domains, which can stall a lookup of a missing record for seconds.
	 */
	private function getFullyQualifiedDomain(): string {
		return $this->getDomain() . '.';
	}

	public function autoconfig(): ?AutoconfigResult {
		if (!$this->autoconfigLoaded) {
			$this->autoconfig = $this->autoconfigLookup->lookup($this);
			$this->autoconfigLoaded = true;
		}
		return $this->autoconfig;
	}

	/**
	 * @return list<string> MX hosts, most preferred first
	 */
	public function mx(): array {
		if ($this->mx !== null) {
			return $this->mx;
		}

		$records = $this->resolver->resolve($this->getFullyQualifiedDomain(), DNS_MX);
		if ($records === false) {
			$records = [];
		}
		usort($records, static fn (array $a, array $b): int => ($a['pri'] ?? 0) <=> ($b['pri'] ?? 0));
		$this->mx = [];
		foreach ($records as $record) {
			if (!empty($record['target'])) {
				$this->mx[] = $record['target'];
			}
		}
		return $this->mx;
	}

	/**
	 * Look up an RFC 2782 service record of the address's domain
	 *
	 * @param string $service e.g. "_imaps._tcp"
	 * @return list<SrvRecord> ordered by priority, then weight
	 */
	public function srv(string $service): array {
		if (isset($this->srv[$service])) {
			return $this->srv[$service];
		}

		$records = $this->resolver->resolve($service . '.' . $this->getFullyQualifiedDomain(), DNS_SRV);
		if ($records === false) {
			$records = [];
		}
		$result = [];
		foreach ($records as $record) {
			$target = $record['target'] ?? '';
			// RFC 2782: a target of "." means the service is not available
			if ($target === '' || $target === '.') {
				continue;
			}
			$result[] = [
				'target' => $target,
				'port' => (int)($record['port'] ?? 0),
				'priority' => (int)($record['pri'] ?? 0),
				'weight' => (int)($record['weight'] ?? 0),
			];
		}
		usort($result, static fn (array $a, array $b): int => [$a['priority'], -$a['weight']] <=> [$b['priority'], -$b['weight']]);
		return $this->srv[$service] = $result;
	}
}
