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

final class FilterValidatorTest extends TestCase {
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

	public static function dataValid(): \Iterator {
		yield 'no filters' => [[]];
		yield 'filter' => [[self::filter()]];
		yield 'any of' => [[self::filter(['operator' => FilterBuilder::OPERATOR_ANY])]];
		yield 'priority as numeric string' => [[self::filter(['priority' => '20'])]];
		yield 'all incoming messages' => [[self::filter(['operator' => FilterBuilder::OPERATOR_TRUE, 'tests' => []])]];
		yield 'stored without conditions' => [[self::filter(['tests' => []])]];
		yield 'stored with empty values' => [[self::filter(['tests' => [['field' => 'subject', 'operator' => 'contains', 'values' => []]]])]];
		yield 'stored with empty mailbox' => [[self::filter(['enable' => false, 'actions' => [['type' => 'fileinto', 'mailbox' => '']]])]];
		yield 'all action types' => [[self::filter(['actions' => [
			['type' => 'fileinto', 'mailbox' => 'Archive'],
			['type' => 'addflag', 'flag' => 'Important'],
			['type' => 'addsystemflag', 'flag' => '\\Seen'],
			['type' => 'redirect', 'recipient' => 'alice@example.org'],
			['type' => 'keep'],
			['type' => 'stop'],
		]])]];
	}

	/**
	 * @dataProvider dataInvalid
	 */
	public function testInvalid(array $filters, string $message): void {
		$this->expectException(InvalidFilterException::class);
		$this->expectExceptionMessage($message);

		$this->validator->validate($filters);
	}

	public static function dataInvalid(): \Iterator {
		yield 'filters not a list' => [['a' => self::filter()], 'Filters must be a list'];
		yield 'filter not an array' => [['filter'], 'Filter 0 has an invalid definition'];
		yield 'reports the position' => [[self::filter(), self::filter(['name' => ''])], 'Filter 1 has an invalid name'];
		yield 'missing name' => [[self::filter(['name' => null])], 'Filter 0 has an invalid name'];
		yield 'name with line break' => [[self::filter(['name' => "Marketing\r\nkeep;"])], 'Filter 0 has an invalid name'];
		yield 'enable not a bool' => [[self::filter(['enable' => 'true'])], 'Filter 0 has an invalid enable'];
		yield 'negative priority' => [[self::filter(['priority' => '-1'])], 'Filter 0 has an invalid priority'];
		yield 'priority not a number' => [[self::filter(['priority' => '10 or'])], 'Filter 0 has an invalid priority'];
		yield 'unknown operator' => [[self::filter(['operator' => 'not'])], 'Filter 0 has an invalid operator'];
		yield 'tests not a list' => [[self::filter(['tests' => 'from'])], 'Filter 0 has an invalid tests'];
		yield 'all incoming messages with conditions' => [[self::filter(['operator' => FilterBuilder::OPERATOR_TRUE])], 'Filter 0 has an invalid tests'];
		yield 'unknown test field' => [[self::filter(['tests' => [['field' => 'cc', 'operator' => 'is', 'values' => []]]])], 'Filter 0 has an invalid test'];
		yield 'unknown test operator' => [[self::filter(['tests' => [['field' => 'to', 'operator' => 'is" "x', 'values' => []]]])], 'Filter 0 has an invalid test'];
		yield 'values not a list' => [[self::filter(['tests' => [['field' => 'to', 'operator' => 'is', 'values' => 'bob']]])], 'Filter 0 has an invalid test'];
		yield 'value not a string' => [[self::filter(['tests' => [['field' => 'to', 'operator' => 'is', 'values' => [['bob']]]]])], 'Filter 0 has an invalid test'];
		yield 'actions not a list' => [[self::filter(['actions' => 'stop'])], 'Filter 0 has an invalid actions'];
		yield 'unknown action' => [[self::filter(['actions' => [['type' => 'discard']]])], 'Filter 0 has an invalid action'];
		yield 'action without parameter' => [[self::filter(['actions' => [['type' => 'redirect']]])], 'Filter 0 has an invalid action'];
	}

	public function testHttpCode(): void {
		$exception = InvalidFilterException::invalidProperty(0, 'name');

		$httpCode = $exception->getHttpCode();

		$this->assertSame(Http::STATUS_UNPROCESSABLE_ENTITY, $httpCode);
	}
}
