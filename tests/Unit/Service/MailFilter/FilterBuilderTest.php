<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\MailFilter;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\IMAP\ImapFlag;
use OCA\Mail\Service\MailFilter\FilterBuilder;

class FilterBuilderTest extends TestCase {
	private FilterBuilder $builder;

	public function setUp(): void {
		parent::setUp();
		$this->builder = new FilterBuilder(new ImapFlag());
	}

	/**
	 * @dataProvider dataBuild
	 */
	public function testBuild(string $testName): void {
		$untouchedScript = '# Hello, this is a test';

		$filters = json_decode(
			file_get_contents(self::getTestFolder() . $testName . '.json'),
			true,
			512,
			JSON_THROW_ON_ERROR
		);

		$script = $this->builder->buildSieveScript($filters, $untouchedScript);

		// the .sieve files have \r\n line endings
		$script .= "\r\n";

		$this->assertStringEqualsFile(
			self::getTestFolder() . $testName . '.sieve',
			$script
		);
	}

	public function dataBuild(): array {
		$files = glob(self::getTestFolder() . 'builder*.json');
		$tests = [];

		foreach ($files as $file) {
			$filename = pathinfo($file, PATHINFO_FILENAME);
			$tests[$filename] = [$filename];
		}

		return $tests;
	}

	public static function getTestFolder(): string {
		return __DIR__ . '/../../../data/mail-filter/';
	}
}
