<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\MailFilter;

use OCA\Mail\Exception\ImapFlagEncodingException;
use OCA\Mail\Exception\InvalidFilterDefinitionException;
use OCA\Mail\IMAP\ImapFlag;
use OCA\Mail\Sieve\SieveUtils;

class DefinitionValidator
{

	private const TEST_FIELDS = [
		'from',
		'subject',
		'to'
	];

	private const TEST_OPERATORS = [
		'contains',
		'is',
		'matches'
	];

	private const ACTION_TYPES = [
		'addflag',
		'fileinto',
		'keep',
		'stop',
	];

	public function __construct(private ImapFlag $imapFlag) {
	}

	/**
	 * @throws InvalidFilterDefinitionException
	 */
	public function validate(array $filters): void
	{
		foreach ($filters as $filter) {
			$this->isValidFilter($filter);

			foreach ($filter['tests'] as $test) {
				$this->isValidTest($test);
			}

			foreach ($filter['actions'] as $action) {
				$this->isValidAction($action);
			}
		}
	}

	/**
	 * @throws InvalidFilterDefinitionException
	 */
	private function isValidFilter(array $filter): void
	{
		if (!isset($filter['name'])) {
			throw new InvalidFilterDefinitionException('Filter name is missing');
		}
		if (!isset($filter['enable'])) {
			throw new InvalidFilterDefinitionException('Filter enable is missing');
		}
		if (!isset($filter['priority'])) {
			throw new InvalidFilterDefinitionException('Filter priority is missing');
		}
		if (!isset($filter['tests'])) {
			throw new InvalidFilterDefinitionException('Filter tests are missing');
		}
		if (!isset($filter['actions'])) {
			throw new InvalidFilterDefinitionException('Filter actions are missing');
		}
	}

	/**
	 * @throws InvalidFilterDefinitionException
	 */
	private function isValidTest(array $test): void
	{
		if (!isset($test['field'])) {
			throw new InvalidFilterDefinitionException('Test field is missing');
		}
		if (!isset($test['operator'])) {
			throw new InvalidFilterDefinitionException('Test operator is missing');
		}
		if (!isset($test['values'])) {
			throw new InvalidFilterDefinitionException('Test values are missing');
		}

		if (!in_array($test['field'], self::TEST_FIELDS, true)) {
			throw new InvalidFilterDefinitionException('Invalid test field');
		}

		if (!in_array($test['operator'], self::TEST_OPERATORS, true)) {
			throw new InvalidFilterDefinitionException('Invalid test operator');
		}
	}

	private function isValidAction(array $action): void {
		if (!isset($action['type'])) {
			throw new InvalidFilterDefinitionException('Action type is missing');
		}
		if ($action['type'] === 'fileinto' && !isset($action['mailbox'])) {
			throw new InvalidFilterDefinitionException('Action mailbox is missing');
		}
		if ( $action['type'] === 'addflag' && !isset($action['flag'])) {
			throw new InvalidFilterDefinitionException('Action flag is missing');
		}
	}
}
