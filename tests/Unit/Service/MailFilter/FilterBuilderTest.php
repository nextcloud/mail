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
	private string $testFolder;

	public function __construct(?string $name = null, array $data = [], $dataName = '') {
		parent::__construct($name, $data, $dataName);
		$this->testFolder = __DIR__ . '/../../../data/mail-filter/';
	}

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
			file_get_contents($this->testFolder . $testName . '.json'),
			true,
			512,
			JSON_THROW_ON_ERROR
		);

		$script = $this->builder->buildSieveScript($filters, $untouchedScript);

		// the .sieve files have \r\n line endings
		$script .= "\r\n";

		$this->assertStringEqualsFile(
			$this->testFolder . $testName . '.sieve',
			$script
		);
	}

	public function dataBuild(): array {
		$files = glob($this->testFolder . 'builder*.json');
		$tests = [];

		foreach ($files as $file) {
			$filename = pathinfo($file, PATHINFO_FILENAME);
			$tests[$filename] = [$filename];
		}

		return $tests;
	}
}
