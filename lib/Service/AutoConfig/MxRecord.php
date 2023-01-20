<?php

declare(strict_types=1);

/**
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

namespace OCA\Mail\Service\AutoConfig;

use Psr\Log\LoggerInterface;

class MxRecord {
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
		if (getmxrr($host, $mxRecords, $mxWeight) === false) {
			$this->logger->debug("no MX records for host <$host> found");
			return [];
		}
		$mxRecords = array_filter($mxRecords, static function ($record) {
			return !empty($record);
		});
		$this->logger->debug("found " . count($mxRecords) . " MX records for host <$host>");
		if (empty(($mxRecords))) {
			return [];
		}

		// TODO: sort by weight
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
