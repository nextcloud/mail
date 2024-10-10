<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\MailFilter;

use OCA\Mail\Exception\ImapFlagEncodingException;
use OCA\Mail\IMAP\ImapFlag;
use OCA\Mail\Sieve\SieveUtils;

class FilterBuilder {
	private const SEPARATOR = '### Nextcloud Mail: Filters ### DON\'T EDIT ###';
	private const DATA_MARKER = '# FILTER: ';
	private const SIEVE_NEWLINE = "\r\n";

	public function __construct(
		private ImapFlag $imapFlag,
	) {
	}


	public function buildSieveScript(array $filters, string $untouchedScript): string {
		$commands = [];
		$extensions = [];

		foreach ($filters as $filter) {
			if ($filter['enable'] === false) {
				continue;
			}

			$commands[] = '# ' . $filter['name'];

			$tests = [];
			foreach ($filter['tests'] as $test) {
				if ($test['field'] === 'subject') {
					$tests[] = sprintf(
						'header :%s "Subject" %s',
						$test['operator'],
						SieveUtils::stringList($test['values']),
					);
				}
				if ($test['field'] === 'to') {
					$tests[] = sprintf(
						'address :%s :all "To" %s',
						$test['operator'],
						SieveUtils::stringList($test['values']),
					);
				}
				if ($test['field'] === 'from') {
					$tests[] = sprintf(
						'address :%s :all "From" %s',
						$test['operator'],
						SieveUtils::stringList($test['values']),
					);
				}
			}

			if (count($tests) === 0) {
				// skip filter without tests
				$commands[] = '# No valid tests found';
				continue;
			}

			$actions = [];
			foreach ($filter['actions'] as $action) {
				if ($action['type'] === 'fileinto') {
					$extensions[] = 'fileinto';
					$actions[] = sprintf(
						'fileinto "%s";',
						SieveUtils::escapeString($action['mailbox'])
					);
				}
				if ($action['type'] === 'addflag') {
					$extensions[] = 'imap4flags';
					$actions[] = sprintf(
						'addflag "%s";',
						SieveUtils::escapeString($this->sanitizeFlag($action['flag']))
					);
				}
				if ($action['type'] === 'keep') {
					$actions[] = 'keep;';
				}
				if ($action['type'] === 'stop') {
					$actions[] = 'stop;';
				}
			}

			if (count($tests) > 1) {
				$ifTest = sprintf('%s (%s)', $filter['operator'], implode(', ', $tests));
			} else {
				$ifTest = $tests[0];
			}

			$actions = array_map(
				static fn ($action) => "\t" . $action,
				$actions
			);

			$ifBlock = sprintf(
				"if %s {\r\n%s\r\n}",
				$ifTest,
				implode(self::SIEVE_NEWLINE, $actions)
			);

			$commands[] = $ifBlock;
		}

		$lines = [];

		$extensions = array_unique($extensions);
		if (count($extensions) > 0) {
			$lines[] = self::SEPARATOR;
			$lines[] = 'require ' . SieveUtils::stringList($extensions) . ';';
			$lines[] = self::SEPARATOR;
		}

		/*
		 * Using implode("\r\n", $lines) may introduce an extra newline if the original script already ends with one.
		 * There may be a cleaner solution, but I couldn't find one that works seamlessly with Filter and Autoresponder.
		 * Feel free to give it a try!
		 */
		if (str_ends_with($untouchedScript, self::SIEVE_NEWLINE . self::SIEVE_NEWLINE)) {
			$untouchedScript = substr($untouchedScript, 0, -2);
		}
		$lines[] = $untouchedScript;

		if (count($filters) > 0) {
			$lines[] = self::SEPARATOR;
			$lines[] = self::DATA_MARKER . json_encode($this->sanitizeDefinition($filters), JSON_THROW_ON_ERROR);
			array_push($lines, ...$commands);
			$lines[] = self::SEPARATOR;
		}

		return implode(self::SIEVE_NEWLINE, $lines);
	}

	private function sanitizeFlag(string $flag): string {
		try {
			return $this->imapFlag->create($flag);
		} catch (ImapFlagEncodingException) {
			return 'placeholder_for_invalid_label';
		}
	}

	private function sanitizeDefinition(array $filters): array {
		return array_map(static function ($filter) {
			unset($filter['accountId'], $filter['id']);
			$filter['tests'] = array_map(static function ($test) {
				unset($test['id']);
				return $test;
			}, $filter['tests']);
			$filter['actions'] = array_map(static function ($action) {
				unset($action['id']);
				return $action;
			}, $filter['actions']);
			$filter['priority'] = (int)$filter['priority'];
			return $filter;
		}, $filters);
	}
}
