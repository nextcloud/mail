<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\MailFilter;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\MailFilter\FilterState;

class FilterStateTest extends TestCase {
	public function testConstructor(): void {
		$filters = ['filter1' => ['name' => 'Test']];

		$state = new FilterState($filters);

		$this->assertInstanceOf(FilterState::class, $state);
	}

	public function testFromJson(): void {
		$data = ['filter1' => ['name' => 'Test Filter']];

		$state = FilterState::fromJson($data);

		$this->assertInstanceOf(FilterState::class, $state);
		$this->assertSame($data, $state->getFilters());
	}

	public function testGetFilters(): void {
		$filters = [
			'filter1' => ['name' => 'Important'],
			'filter2' => ['name' => 'Archive'],
		];

		$state = new FilterState($filters);

		$this->assertSame($filters, $state->getFilters());
	}

	public function testJsonSerialize(): void {
		$filters = ['filter1' => ['name' => 'Test']];
		$state = new FilterState($filters);

		$result = $state->jsonSerialize();

		$this->assertSame($filters, $result);
	}

	public function testDefaultVersion(): void {
		$this->assertSame(1, FilterState::DEFAULT_VERSION);
	}

	public function testEmptyFilters(): void {
		$filters = [];
		$state = new FilterState($filters);

		$this->assertSame([], $state->getFilters());
		$this->assertSame([], $state->jsonSerialize());
	}

	public function testFromJsonWithEmptyData(): void {
		$data = [];

		$state = FilterState::fromJson($data);

		$this->assertSame([], $state->getFilters());
	}

	public function testComplexFilterStructure(): void {
		$filters = [
			'filter1' => [
				'name' => 'Newsletters',
				'rules' => [
					['field' => 'from', 'value' => 'newsletter@example.com'],
				],
				'actions' => [
					['type' => 'folder', 'value' => 'Newsletters'],
				],
			],
		];
		$state = new FilterState($filters);

		$this->assertSame($filters, $state->getFilters());
		$this->assertSame($filters, $state->jsonSerialize());
	}
}
