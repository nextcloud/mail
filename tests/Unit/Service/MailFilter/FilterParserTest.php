<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\Service\MailFilter;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\MailFilter\FilterParser;

class FilterParserTest extends TestCase {
	private FilterParser $filterParser;

	private string $testFolder;

	public function __construct(?string $name = null, array $data = [], $dataName = '') {
		parent::__construct($name, $data, $dataName);
		$this->testFolder = __DIR__ . '/../../../data/mail-filter/';
	}

	protected function setUp(): void {
		parent::setUp();

		$this->filterParser = new FilterParser();
	}

	public function testParse1(): void {
		$script = file_get_contents($this->testFolder . 'parser1.sieve');

		$state = $this->filterParser->parseFilterState($script);
		$filters = $state->getFilters();

		$this->assertCount(1, $filters);
		$this->assertSame('Test 1', $filters[0]['name']);
		$this->assertTrue($filters[0]['enable']);
		$this->assertSame('allof', $filters[0]['operator']);
		$this->assertSame(10, $filters[0]['priority']);

		$this->assertCount(1, $filters[0]['tests']);
		$this->assertSame('from', $filters[0]['tests'][0]['field']);
		$this->assertSame('is', $filters[0]['tests'][0]['operator']);
		$this->assertEquals(['alice@example.org', 'bob@example.org'], $filters[0]['tests'][0]['values']);

		$this->assertCount(1, $filters[0]['actions']);
		$this->assertSame('addflag', $filters[0]['actions'][0]['type']);
		$this->assertSame('Alice and Bob', $filters[0]['actions'][0]['flag']);
	}

	public function testParse2(): void {
		$script = file_get_contents($this->testFolder . 'parser2.sieve');

		$state = $this->filterParser->parseFilterState($script);
		$filters = $state->getFilters();

		$this->assertCount(1, $filters);
		$this->assertSame('Test 2', $filters[0]['name']);
		$this->assertTrue($filters[0]['enable']);
		$this->assertSame('anyof', $filters[0]['operator']);
		$this->assertSame(20, $filters[0]['priority']);

		$this->assertCount(2, $filters[0]['tests']);
		$this->assertSame('subject', $filters[0]['tests'][0]['field']);
		$this->assertSame('contains', $filters[0]['tests'][0]['operator']);
		$this->assertEquals(['Project-A', 'Project-B'], $filters[0]['tests'][0]['values']);
		$this->assertSame('from', $filters[0]['tests'][1]['field']);
		$this->assertSame('is', $filters[0]['tests'][1]['operator']);
		$this->assertEquals(['john@example.org'], $filters[0]['tests'][1]['values']);

		$this->assertCount(1, $filters[0]['actions']);
		$this->assertSame('fileinto', $filters[0]['actions'][0]['type']);
		$this->assertSame('Test Data', $filters[0]['actions'][0]['mailbox']);
	}
}
