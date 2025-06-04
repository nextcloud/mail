<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\AutoConfig;

use Psr\Log\LoggerInterface;
use function array_combine;
use function asort;

final class MxRecord {
	/** @var LoggerInterface */
	private $logger;

	public function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
	}

	/**
	 * @param $host
	 * @return string[]
	 */
	public function query(string $host) {
		if (getmxrr($host, $mxRecords, $mxWeights) === false) {
			$this->logger->debug("no MX records for host <$host> found");
			return [];
		}

		// Sort MX records by weight
		$sortedRecords = array_combine($mxRecords, $mxWeights);
		asort($sortedRecords, SORT_NUMERIC);

		$mxRecords = array_filter(array_keys($sortedRecords), static function ($record) {
			return !empty($record);
		});
		$this->logger->debug('found ' . count($sortedRecords) . " MX records for host <$host>");
		if (empty(($mxRecords))) {
			return [];
		}

		return $this->sanitizedRecords($mxRecords);
	}

	private function stripSubdomain(string $domain): string {
		$labels = explode('.', $domain);

		$top = count($labels) >= 2 ? array_pop($labels) : '';
		$second = array_pop($labels);

		return $second . '.' . $top;
	}

	private function sanitizedRecords(array $mxHosts): array {
		return array_unique(array_merge($mxHosts, array_map([$this, 'stripSubdomain'], $mxHosts)));
	}
}
