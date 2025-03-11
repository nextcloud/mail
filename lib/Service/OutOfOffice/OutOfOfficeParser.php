<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\OutOfOffice;

use DateTimeImmutable;
use DateTimeZone;
use JsonException;
use OCA\Mail\Exception\OutOfOfficeParserException;
use OCA\Mail\Sieve\SieveUtils;

/**
 * Parses and builds out-of-office states from/to sieve scripts.
 */
class OutOfOfficeParser {
	private const SEPARATOR = '### Nextcloud Mail: Vacation Responder ### DON\'T EDIT ###';
	private const DATA_MARKER = '# DATA: ';

	private const STATE_COPY = 0;
	private const STATE_SKIP = 1;

	private DateTimeZone $utc;

	public function __construct() {
		$this->utc = new DateTimeZone('UTC');
	}

	/**
	 * @throws OutOfOfficeParserException
	 */
	public function parseOutOfOfficeState(string $sieveScript): OutOfOfficeParserResult {
		$data = null;
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
							$jsonData = json_decode($json, true, 10, JSON_THROW_ON_ERROR);
						} catch (JsonException $e) {
							throw new OutOfOfficeParserException(
								'Failed to parse out-of-office state json: ' . $e->getMessage(),
								0,
								$e,
							);
						}
						$data = OutOfOfficeState::fromJson($jsonData);
					}
					break;
				default:
					throw new OutOfOfficeParserException('Reached an invalid state');
			}
			$state = $nextState;
		}

		return new OutOfOfficeParserResult($data, $sieveScript, implode("\r\n", $scriptOut));
	}

	/**
	 * @param string[] $allowedRecipients Respond to envelopes that are addressed to the given addresses.
	 *                                    Should be the main address and aliases of the account.
	 *                                    An empty array will leave the decision to the sieve implementation.
	 *
	 * @throws OutOfOfficeParserException If the given out-of-office state is missing required fields.
	 * @throws JSONException If the given out-of-office state can't be serialized to JSON.
	 */
	public function buildSieveScript(
		OutOfOfficeState $state,
		string $untouchedScript,
		array $allowedRecipients,
	): string {
		// No need to persist dates if not enabled
		if (!$state->isEnabled()) {
			$state->setStart(null);
			$state->setEnd(null);
		}

		$stateJsonString = json_encode($state, JSON_THROW_ON_ERROR);

		if (!$state->isEnabled()) {
			//unset($jsonData['start'], $jsonString['end']);
			return implode("\r\n", [
				$untouchedScript,
				self::SEPARATOR,
				self::DATA_MARKER . $stateJsonString,
				self::SEPARATOR,
			]);
		}

		if ($state->getStart() === null) {
			throw new OutOfOfficeParserException('Out-of-office state is missing a start date');
		}

		$formattedStart = $this->formatDateForSieve($state->getStart());
		if ($state->getEnd() !== null) {
			$formattedEnd = $this->formatDateForSieve($state->getEnd());
			$condition = "allof(currentdate :value \"ge\" \"iso8601\" \"$formattedStart\", currentdate :value \"le\" \"iso8601\" \"$formattedEnd\")";
		} else {
			$condition = "currentdate :value \"ge\" \"iso8601\" \"$formattedStart\"";
		}

		$escapedSubject = SieveUtils::escapeString($state->getSubject());
		$vacation = [
			'vacation',
			':days 4',
			":subject \"$escapedSubject\"",
		];

		if (!empty($allowedRecipients)) {
			$formattedRecipients = array_map(static function (string $recipient) {
				return "\"$recipient\"";
			}, $allowedRecipients);
			$joinedRecipients = implode(', ', $formattedRecipients);
			$vacation[] = ":addresses [$joinedRecipients]";
		}

		$escapedMessage = SieveUtils::escapeString($state->getMessage());
		$vacation[] = "\"$escapedMessage\"";
		$vacationCommand = implode(' ', $vacation);

		$subjectSection = [
			'set "subject" "";',
			'if header :matches "subject" "*" {',
			"\tset \"subject\" \"\${1}\";",
			'}',
		];

		$hasSubjectPlaceholder = str_contains($state->getSubject(), '${subject}')
			|| str_contains($state->getMessage(), '${subject}');

		$requireSection = [
			self::SEPARATOR,
			'require "date";',
			'require "relational";',
			'require "vacation";',
		];
		if ($hasSubjectPlaceholder) {
			$requireSection[] = 'require "variables";';
		}
		$requireSection[] = self::SEPARATOR;

		$vacationSection = [
			self::SEPARATOR,
			self::DATA_MARKER . $stateJsonString,
		];
		if ($hasSubjectPlaceholder) {
			$vacationSection = array_merge($vacationSection, $subjectSection);
		}
		$vacationSection = array_merge($vacationSection, [
			"if $condition {",
			"\t$vacationCommand;",
			'}',
			self::SEPARATOR,
		]);

		return implode("\r\n", array_merge(
			$requireSection,
			[$untouchedScript],
			$vacationSection,
		));
	}

	private function formatDateForSieve(DateTimeImmutable $date): string {
		return $date->setTimezone($this->utc)->format('Y-m-d\TH:i:s\Z');
	}
}
