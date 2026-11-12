<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\MailFilter;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Exception\InvalidFilterException;
use OCA\Mail\Service\MailFilter\FilterBuilder;
use OCA\Mail\Service\MailFilter\FilterValidator;
use OCP\AppFramework\Http;

class FilterValidatorTest extends TestCase {
	private FilterValidator $validator;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new FilterValidator();
	}

	private static function filter(array $overrides = []): array {
		return array_merge([
			'id' => 123,
			'name' => 'Marketing',
			'enable' => true,
			'operator' => FilterBuilder::OPERATOR_ALL,
			'tests' => [
				['id' => 456, 'field' => 'from', 'operator' => 'is', 'values' => ['marketing@example.org']],
			],
			'actions' => [
				['id' => 789, 'type' => 'fileinto', 'mailbox' => 'Marketing'],
				['type' => 'stop'],
			],
			'priority' => 10,
		], $overrides);
	}

	/**
	 * @dataProvider dataValid
	 */
	public function testValid(array $filters): void {
		$this->expectNotToPerformAssertions();

		$this->validator->validate($filters);
	}

	public static function dataValid(): array {
		return [
			'no filters' => [[]],
			'filter' => [[self::filter()]],
			'any of' => [[self::filter(['operator' => FilterBuilder::OPERATOR_ANY])]],
			'priority as numeric string' => [[self::filter(['priority' => '20'])]],
			'all incoming messages' => [[self::filter(['operator' => FilterBuilder::OPERATOR_TRUE, 'tests' => []])]],
			'stored without conditions' => [[self::filter(['tests' => []])]],
			'stored with empty values' => [[self::filter(['tests' => [['field' => 'subject', 'operator' => 'contains', 'values' => []]]])]],
			'stored with empty mailbox' => [[self::filter(['enable' => false, 'actions' => [['type' => 'fileinto', 'mailbox' => '']]])]],
			'all action types' => [[self::filter(['actions' => [
				['type' => 'fileinto', 'mailbox' => 'Archive'],
				['type' => 'addflag', 'flag' => 'Important'],
				['type' => 'addsystemflag', 'flag' => '\\Seen'],
				['type' => 'redirect', 'recipient' => 'alice@example.org'],
				['type' => 'keep'],
				['type' => 'stop'],
			]])]],
		];
	}

	/**
	 * @dataProvider dataInvalid
	 */
	public function testInvalid(array $filters, string $message): void {
		$this->expectException(InvalidFilterException::class);
		$this->expectExceptionMessage($message);

		$this->validator->validate($filters);
	}

	public static function dataInvalid(): array {
		return [
			'filters not a list' => [['a' => self::filter()], 'Filters must be a list'],
			'filter not an array' => [['filter'], 'Filter 0 has an invalid definition'],
			'reports the position' => [[self::filter(), self::filter(['name' => ''])], 'Filter 1 has an invalid name'],
			'missing name' => [[self::filter(['name' => null])], 'Filter 0 has an invalid name'],
			'name with line break' => [[self::filter(['name' => "Marketing\r\nkeep;"])], 'Filter 0 has an invalid name'],
			'enable not a bool' => [[self::filter(['enable' => 'true'])], 'Filter 0 has an invalid enable'],
			'negative priority' => [[self::filter(['priority' => '-1'])], 'Filter 0 has an invalid priority'],
			'priority not a number' => [[self::filter(['priority' => '10 or'])], 'Filter 0 has an invalid priority'],
			'unknown operator' => [[self::filter(['operator' => 'not'])], 'Filter 0 has an invalid operator'],
			'tests not a list' => [[self::filter(['tests' => 'from'])], 'Filter 0 has an invalid tests'],
			'all incoming messages with conditions' => [[self::filter(['operator' => FilterBuilder::OPERATOR_TRUE])], 'Filter 0 has an invalid tests'],
			'unknown test field' => [[self::filter(['tests' => [['field' => 'cc', 'operator' => 'is', 'values' => []]]])], 'Filter 0 has an invalid test'],
			'unknown test operator' => [[self::filter(['tests' => [['field' => 'to', 'operator' => 'is" "x', 'values' => []]]])], 'Filter 0 has an invalid test'],
			'values not a list' => [[self::filter(['tests' => [['field' => 'to', 'operator' => 'is', 'values' => 'bob']]])], 'Filter 0 has an invalid test'],
			'value not a string' => [[self::filter(['tests' => [['field' => 'to', 'operator' => 'is', 'values' => [['bob']]]]])], 'Filter 0 has an invalid test'],
			'actions not a list' => [[self::filter(['actions' => 'stop'])], 'Filter 0 has an invalid actions'],
			'unknown action' => [[self::filter(['actions' => [['type' => 'discard']]])], 'Filter 0 has an invalid action'],
			'action without parameter' => [[self::filter(['actions' => [['type' => 'redirect']]])], 'Filter 0 has an invalid action'],
		];
	}

	public function testHttpCode(): void {
		$exception = InvalidFilterException::invalidProperty(0, 'name');

		$httpCode = $exception->getHttpCode();

		$this->assertSame(Http::STATUS_UNPROCESSABLE_ENTITY, $httpCode);
	}
}
