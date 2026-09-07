<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Migration;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\ConfigLexicon;
use OCA\Mail\Migration\Version5120Date20260820000000;
use OCP\AppFramework\Services\IAppConfig;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;

class Version5120Date20260820000000Test extends TestCase {

	private IAppConfig&MockObject $appConfig;
	private IOutput&MockObject $output;
	private Version5120Date20260820000000 $migration;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->output = $this->createMock(IOutput::class);
		$this->migration = new Version5120Date20260820000000($this->appConfig);
	}

	public function testHealsPresentBoolKeys(): void {
		$this->appConfig->method('getAllAppValues')
			->willReturn([
				ConfigLexicon::ALLOW_NEW_MAIL_ACCOUNTS => true,
				ConfigLexicon::LLM_PROCESSING => false,
				ConfigLexicon::IMPORTANCE_CLASSIFICATION_DEFAULT => false,
				ConfigLexicon::INDEX_CONTEXT_CHAT_DEFAULT => true,
			]);

		$this->appConfig->expects(self::exactly(4))
			->method('deleteAppValue')
			->withConsecutive(
				[ConfigLexicon::ALLOW_NEW_MAIL_ACCOUNTS],
				[ConfigLexicon::LLM_PROCESSING],
				[ConfigLexicon::IMPORTANCE_CLASSIFICATION_DEFAULT],
				[ConfigLexicon::INDEX_CONTEXT_CHAT_DEFAULT],
			);
		$this->appConfig->expects(self::exactly(4))
			->method('setAppValueBool')
			->withConsecutive(
				[ConfigLexicon::ALLOW_NEW_MAIL_ACCOUNTS, true],
				[ConfigLexicon::LLM_PROCESSING, false],
				[ConfigLexicon::IMPORTANCE_CLASSIFICATION_DEFAULT, false],
				[ConfigLexicon::INDEX_CONTEXT_CHAT_DEFAULT, true],
			);

		$this->migration->postSchemaChange($this->output, fn () => $this->createMock(ISchemaWrapper::class), []);
	}

	public function testOnlyHealsPresentKeys(): void {
		$this->appConfig->method('getAllAppValues')
			->willReturn([
				ConfigLexicon::IMPORTANCE_CLASSIFICATION_DEFAULT => false,
				ConfigLexicon::LAYOUT_MESSAGE_VIEW => 'threaded',
			]);

		$this->appConfig->expects(self::once())
			->method('deleteAppValue')
			->with(ConfigLexicon::IMPORTANCE_CLASSIFICATION_DEFAULT);
		$this->appConfig->expects(self::once())
			->method('setAppValueBool')
			->with(ConfigLexicon::IMPORTANCE_CLASSIFICATION_DEFAULT, false);

		$this->migration->postSchemaChange($this->output, fn () => $this->createMock(ISchemaWrapper::class), []);
	}

	public function testDoesNothingWhenNoKeysPresent(): void {
		$this->appConfig->method('getAllAppValues')
			->willReturn([
				ConfigLexicon::LAYOUT_MESSAGE_VIEW => 'threaded',
			]);

		$this->appConfig->expects(self::never())
			->method('deleteAppValue');
		$this->appConfig->expects(self::never())
			->method('setAppValueBool');

		$this->migration->postSchemaChange($this->output, fn () => $this->createMock(ISchemaWrapper::class), []);
	}
}
