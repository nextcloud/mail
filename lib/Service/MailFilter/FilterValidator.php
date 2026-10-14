<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\MailFilter;

use OCA\Mail\Exception\InvalidFilterException;

class FilterValidator {
	private const OPERATORS = [FilterBuilder::OPERATOR_ALL, FilterBuilder::OPERATOR_ANY, FilterBuilder::OPERATOR_TRUE];
	private const TEST_FIELDS = ['subject', 'from', 'to'];
	private const TEST_OPERATORS = ['is', 'contains', 'matches'];
	private const ACTION_PARAMETERS = [
		'fileinto' => 'mailbox',
		'addflag' => 'flag',
		'addsystemflag' => 'flag',
		'keep' => null,
		'stop' => null,
		'redirect' => 'recipient',
	];

	/**
	 * @throws InvalidFilterException
	 */
	public function validate(array $filters): void {
		if (!array_is_list($filters)) {
			throw new InvalidFilterException('Filters must be a list');
		}

		foreach ($filters as $index => $filter) {
			$this->validateFilter($index, $filter);
		}
	}

	/**
	 * @throws InvalidFilterException
	 */
	private function validateFilter(int $index, mixed $filter): void {
		if (!is_array($filter)) {
			throw InvalidFilterException::invalidProperty($index, 'definition');
		}

		$name = $filter['name'] ?? null;
		if (!is_string($name) || $name === '' || preg_match('/[\r\n]/', $name) === 1) {
			throw InvalidFilterException::invalidProperty($index, 'name');
		}

		if (!is_bool($filter['enable'] ?? null)) {
			throw InvalidFilterException::invalidProperty($index, 'enable');
		}

		$priority = $filter['priority'] ?? null;
		if (!is_int($priority) && !(is_string($priority) && ctype_digit($priority))) {
			throw InvalidFilterException::invalidProperty($index, 'priority');
		}

		$operator = $filter['operator'] ?? null;
		if (!in_array($operator, self::OPERATORS, true)) {
			throw InvalidFilterException::invalidProperty($index, 'operator');
		}

		$tests = $filter['tests'] ?? null;
		if (!is_array($tests) || !array_is_list($tests)
			|| ($operator === FilterBuilder::OPERATOR_TRUE && $tests !== [])) {
			throw InvalidFilterException::invalidProperty($index, 'tests');
		}

		foreach ($tests as $test) {
			$this->validateTest($index, $test);
		}

		$actions = $filter['actions'] ?? null;
		if (!is_array($actions) || !array_is_list($actions)) {
			throw InvalidFilterException::invalidProperty($index, 'actions');
		}

		foreach ($actions as $action) {
			$this->validateAction($index, $action);
		}
	}

	/**
	 * @throws InvalidFilterException
	 */
	private function validateTest(int $index, mixed $test): void {
		if (!is_array($test)
			|| !in_array($test['field'] ?? null, self::TEST_FIELDS, true)
			|| !in_array($test['operator'] ?? null, self::TEST_OPERATORS, true)) {
			throw InvalidFilterException::invalidProperty($index, 'test');
		}

		$values = $test['values'] ?? null;
		if (!is_array($values) || !array_is_list($values)) {
			throw InvalidFilterException::invalidProperty($index, 'test');
		}

		foreach ($values as $value) {
			if (!is_string($value)) {
				throw InvalidFilterException::invalidProperty($index, 'test');
			}
		}
	}

	/**
	 * @throws InvalidFilterException
	 */
	private function validateAction(int $index, mixed $action): void {
		$type = is_array($action) ? ($action['type'] ?? null) : null;
		if (!is_string($type) || !array_key_exists($type, self::ACTION_PARAMETERS)) {
			throw InvalidFilterException::invalidProperty($index, 'action');
		}

		$parameter = self::ACTION_PARAMETERS[$type];
		if ($parameter !== null && !is_string($action[$parameter] ?? null)) {
			throw InvalidFilterException::invalidProperty($index, 'action');
		}
	}
}
