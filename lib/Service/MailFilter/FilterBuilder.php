<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\MailFilter;

use OCA\Mail\Exception\ImapFlagEncodingException;
use OCA\Mail\IMAP\ImapFlag;

class FilterBuilder {
	private const SEPARATOR = '### Nextcloud Mail: Filters ### DON\'T EDIT ###';
	private const DATA_MARKER = '# DATA: ';
	private const SIEVE_NEWLINE = "\r\n";

	public function __construct(private ImapFlag $imapFlag) {
	}


	public function buildSieveScript(array $filters, string $untouchedScript): string {
		$commands = [];
		$extensions = [];

		foreach ($filters as $filter) {
			if ($filter['enable'] === false) {
				continue;
			}

			$commands[] = '# Filter: ' . $filter['name'];

			$tests = [];
			foreach ($filter['tests'] as $test) {
				if ($test['field'] === 'subject') {
					$tests[] = sprintf(
						'header :%s "Subject" %s',
						$test['operator'],
						$this->stringList($test['values'])
					);
				}
				if ($test['field'] === 'to') {
					$tests[] = sprintf(
						'address :%s :all "To" %s',
						$test['operator'],
						$this->stringList($test['values'])
					);
				}
				if ($test['field'] === 'from') {
					$tests[] = sprintf(
						'address :%s :all "From" %s',
						$test['operator'],
						$this->stringList($test['values'])
					);
				}
			}

			$actions = [];
			foreach ($filter['actions'] as $action) {
				if ($action['type'] === 'fileinto') {
					$extensions[] = 'fileinto';
					$actions[] = sprintf('fileinto "%s";', $action['mailbox']);
				}
				if ($action['type'] === 'addflag') {
					$extensions[] = 'imap4flags';
					$actions[] = sprintf('addflag %s;', $this->stringList($this->sanitizeFlag($action['flag'])));
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

			$ifBlock = sprintf(
				"if %s {\r\n%s\r\n}\r\n",
				$ifTest,
				implode(self::SIEVE_NEWLINE, $actions)
			);

			$commands[] = $ifBlock;
		}

		$extensions = array_unique($extensions);
		$requireSection = [];

		if (count($extensions) > 0) {
			$requireSection[] = self::SEPARATOR;
			$requireSection[] = 'require ' . $this->stringList($extensions) . ';';
			$requireSection[] = self::SEPARATOR;
			$requireSection[] = '';
		}

		$stateJsonString = json_encode($this->sanitizeDefinition($filters), JSON_THROW_ON_ERROR);

		$filterSection = [
			'',
			self::SEPARATOR,
			self::DATA_MARKER . $stateJsonString,
			...$commands,
			self::SEPARATOR,
			'',
		];

		return implode(self::SIEVE_NEWLINE, array_merge(
			$requireSection,
			[$untouchedScript],
			$filterSection,
		));
	}

	private function stringList(string|array $value): string {
		if (is_string($value)) {
			$items = explode(',', $value);
		} else {
			$items = $value;
		}

		$items = array_map([$this, 'quoteString'], $items);

		return '[' . implode(', ', $items) . ']';
	}

	private function quoteString(string $value): string {
		return '"' . $value . '"';
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
			return $filter;
		}, $filters);
	}
}
