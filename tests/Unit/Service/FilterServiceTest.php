<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\IMAP\ImapFlag;
use OCA\Mail\Service\AllowedRecipientsService;
use OCA\Mail\Service\FilterService;
use OCA\Mail\Service\MailFilter\FilterBuilder;
use OCA\Mail\Service\MailFilter\FilterParser;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeParser;
use OCA\Mail\Service\SieveService;
use OCA\Mail\Sieve\NamedSieveScript;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;

class FilterServiceTest extends TestCase {

	private string $testFolder;

	private AllowedRecipientsService $allowedRecipientsService;
	private OutOfOfficeParser $outOfOfficeParser;
	private FilterParser $filterParser;
	private FilterBuilder $filterBuilder;
	private SieveService&MockObject $sieveService;
	private LoggerInterface $logger;
	private FilterService $filterService;

	public function __construct(?string $name = null, array $data = [], $dataName = '') {
		parent::__construct($name, $data, $dataName);
		$this->testFolder = __DIR__ . '/../../data/mail-filter/';
	}

	protected function setUp(): void {
		parent::setUp();

		$this->allowedRecipientsService = $this->createMock(AllowedRecipientsService::class);
		$this->outOfOfficeParser = new OutOfOfficeParser();
		$this->filterParser = new FilterParser();
		$this->filterBuilder = new FilterBuilder(new ImapFlag());
		$this->sieveService = $this->createMock(SieveService::class);
		$this->logger = new TestLogger();

		$this->filterService = new FilterService(
			$this->allowedRecipientsService,
			$this->outOfOfficeParser,
			$this->filterParser,
			$this->filterBuilder,
			$this->sieveService,
			$this->logger
		);
	}

	public function testParse1(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('alice');
		$mailAccount->setEmail('alice@mail.internal');

		$script = new NamedSieveScript(
			'test.sieve',
			file_get_contents($this->testFolder . 'parser1.sieve'),
		);

		$this->sieveService->method('getActiveScript')
			->willReturn($script);

		$result = $this->filterService->parse($mailAccount);

		// Not checking the filters because FilterParserTest.testParser1 uses the same script.
		$this->assertCount(1, $result->getFilters());

		$this->assertEquals("# Hello, this is a test\r\n", $result->getUntouchedSieveScript());
	}

	public function testParse2(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('alice');
		$mailAccount->setEmail('alice@mail.internal');

		$script = new NamedSieveScript(
			'test.sieve',
			file_get_contents($this->testFolder . 'parser2.sieve'),
		);

		$this->sieveService->method('getActiveScript')
			->willReturn($script);

		$result = $this->filterService->parse($mailAccount);

		// Not checking the filters because FilterParserTest.testParser2 uses the same script.
		$this->assertCount(1, $result->getFilters());

		$this->assertEquals("# Hello, this is a test\r\n", $result->getUntouchedSieveScript());
	}

	public function testParse3(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('alice');
		$mailAccount->setEmail('alice@mail.internal');

		$script = new NamedSieveScript(
			'test.sieve',
			file_get_contents($this->testFolder . 'parser3.sieve'),
		);

		$untouchedScript = file_get_contents($this->testFolder . 'parser3_untouched.sieve');

		$this->sieveService->method('getActiveScript')
			->willReturn($script);

		$result = $this->filterService->parse($mailAccount);

		$this->assertCount(0, $result->getFilters());

		$this->assertEquals($untouchedScript, $result->getUntouchedSieveScript());
	}

	public function testParse4(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('alice');
		$mailAccount->setEmail('alice@mail.internal');

		$script = new NamedSieveScript(
			'test.sieve',
			file_get_contents($this->testFolder . 'parser4.sieve'),
		);

		$untouchedScript = file_get_contents($this->testFolder . 'parser4_untouched.sieve');

		$this->sieveService->method('getActiveScript')
			->willReturn($script);

		$result = $this->filterService->parse($mailAccount);

		$filters = $result->getFilters();

		$this->assertCount(1, $filters);
		$this->assertSame('Marketing', $filters[0]['name']);
		$this->assertTrue($filters[0]['enable']);
		$this->assertSame('allof', $filters[0]['operator']);
		$this->assertSame(10, $filters[0]['priority']);

		$this->assertCount(1, $filters[0]['tests']);
		$this->assertSame('from', $filters[0]['tests'][0]['field']);
		$this->assertSame('is', $filters[0]['tests'][0]['operator']);
		$this->assertEquals(['marketing@mail.internal'], $filters[0]['tests'][0]['values']);

		$this->assertCount(1, $filters[0]['actions']);
		$this->assertSame('fileinto', $filters[0]['actions'][0]['type']);
		$this->assertSame('Marketing', $filters[0]['actions'][0]['mailbox']);

		$this->assertEquals($untouchedScript, $result->getUntouchedSieveScript());
	}

	/**
	 * Test case: Add a filter set to a sieve script with autoresponder.
	 */
	public function testUpdate1(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('alice');
		$mailAccount->setEmail('alice@mail.internal');

		$script = new NamedSieveScript(
			'test.sieve',
			file_get_contents($this->testFolder . 'service1.sieve'),
		);

		$filters = json_decode(
			file_get_contents($this->testFolder . 'service1.json'),
			true
		);

		$this->sieveService->method('getActiveScript')
			->willReturn($script);

		$this->sieveService->method('updateActiveScript')
			->willReturnCallback(function (string $userId, int $accountId, string $script) {
				// the .sieve files have \r\n line endings
				$script .= "\r\n";

				$this->assertStringEqualsFile($this->testFolder . 'service1_new.sieve', $script);
			});

		$this->allowedRecipientsService->method('get')
			->willReturn(['alice@mail.internal']);

		$this->filterService->update($mailAccount, $filters);
	}

	/**
	 * Test case: Delete a filter rule from a set.
	 */
	public function testUpdate2(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('alice');
		$mailAccount->setEmail('alice@mail.internal');

		$script = new NamedSieveScript(
			'test.sieve',
			file_get_contents($this->testFolder . 'service2.sieve'),
		);

		$filters = json_decode(
			file_get_contents($this->testFolder . 'service2.json'),
			true
		);

		$this->sieveService->method('getActiveScript')
			->willReturn($script);

		$this->sieveService->method('updateActiveScript')
			->willReturnCallback(function (string $userId, int $accountId, string $script) {
				// the .sieve files have \r\n line endings
				$script .= "\r\n";

				$this->assertStringEqualsFile($this->testFolder . 'service2_new.sieve', $script);
			});

		$this->allowedRecipientsService->method('get')
			->willReturn(['alice@mail.internal']);

		$this->filterService->update($mailAccount, $filters);
	}
}
