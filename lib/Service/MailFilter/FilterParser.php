<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\MailFilter;

use JsonException;
use OCA\Mail\Exception\FilterParserException;

class FilterParser {
	private const SEPARATOR = '### Nextcloud Mail: Filters ### DON\'T EDIT ###';
	private const DATA_MARKER = '# FILTER: ';

	private const STATE_COPY = 0;
	private const STATE_SKIP = 1;

	/**
	 * @throws FilterParserException
	 */
	public function parseFilterState(string $sieveScript): FilterParserResult {
		$filters = [];
		$scriptOut = [];

		$state = self::STATE_COPY;
		$nextState = $state;

		$lines = preg_split('/\r?\n/', $sieveScript);
		foreach ($lines as $line) {
			switch ($state) {
				case self::STATE_COPY:
					if (str_starts_with($line, self::SEPARATOR)) {
						$nextState = self::STATE_SKIP;
					} else {
						$scriptOut[] = $line;
					}
					break;
				case self::STATE_SKIP:
					if (str_starts_with($line, self::SEPARATOR)) {
						$nextState = self::STATE_COPY;
					} elseif (str_starts_with($line, self::DATA_MARKER)) {
						$json = substr($line, strlen(self::DATA_MARKER));

						try {
							$data = json_decode($json, true, 10, JSON_THROW_ON_ERROR);
						} catch (JsonException $e) {
							throw FilterParserException::invalidJson($e);
						}

						if (is_array($data)) {
							array_push($filters, ...$data);
						}
					}
					break;
				default:
					throw FilterParserException::invalidState();
			}
			$state = $nextState;
		}

		return new FilterParserResult($filters, $sieveScript, implode("\r\n", $scriptOut));
	}
}
