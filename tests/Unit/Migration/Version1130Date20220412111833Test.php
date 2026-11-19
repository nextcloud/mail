<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Migration;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use OCA\Mail\Migration\Version1130Date20220412111833;
use OCP\DB\IResult;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\ITempManager;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class Version1130Date20220412111833Test extends TestCase {

	private IDBConnection&MockObject $connection;
	private IResult&MockObject $result;
	private IOutput&MockObject $output;
	private string $tempDir;
	private Version1130Date20220412111833 $migration;

	protected function setUp(): void {
		parent::setUp();

		$this->tempDir = sys_get_temp_dir() . '/mail-migration-test-' . uniqid();
		mkdir($this->tempDir);

		$this->result = $this->createMock(IResult::class);
		$this->output = $this->createMock(IOutput::class);
		$this->connection = $this->createMock(IDBConnection::class);
		$this->connection->method('getQueryBuilder')
			->willReturnCallback(fn () => $this->createQueryBuilder());
		$this->connection->method('getDatabasePlatform')
			->willReturn($this->createMock(AbstractPlatform::class));
		$tempManager = $this->createMock(ITempManager::class);
		$tempManager->method('getTempBaseDir')
			->willReturn($this->tempDir);

		$this->migration = new Version1130Date20220412111833(
			$this->connection,
			$this->createMock(LoggerInterface::class),
			$tempManager,
		);
	}

	protected function tearDown(): void {
		foreach (glob($this->tempDir . '/*') as $file) {
			unlink($file);
		}
		rmdir($this->tempDir);

		parent::tearDown();
	}

	private function createQueryBuilder(): IQueryBuilder&MockObject {
		$qb = $this->createMock(IQueryBuilder::class);
		$qb->method('select')->willReturnSelf();
		$qb->method('from')->willReturnSelf();
		$qb->method('where')->willReturnSelf();
		$qb->method('update')->willReturnSelf();
		$qb->method('set')->willReturnSelf();
		$qb->method('expr')->willReturn($this->createMock(IExpressionBuilder::class));
		$qb->method('executeQuery')->willReturn($this->result);
		return $qb;
	}

	private function readBackup(): string {
		$files = glob($this->tempDir . '/mail_recipients_backup*');
		$this->assertCount(1, $files);
		return file_get_contents($files[0]);
	}

	public function testBackupWithoutRecipientsOnlyHasHeader(): void {
		$this->result->method('fetchAll')
			->willReturn([]);

		$this->migration->preSchemaChange($this->output, fn () => $this->createMock(ISchemaWrapper::class), []);

		$this->assertSame("local_message_id,message_id,type,label,email\n", $this->readBackup());
	}

	public function testBackupIsRfc4180Csv(): void {
		$this->result->method('fetchAll')
			->willReturn([
				[
					'local_message_id' => 3,
					'message_id' => null,
					'type' => 1,
					'label' => 'Jane \"JJ\" Doe',
					'email' => 'jane@example.com',
				],
			]);

		$this->migration->preSchemaChange($this->output, fn () => $this->createMock(ISchemaWrapper::class), []);

		$this->assertSame(
			"local_message_id,message_id,type,label,email\n"
			. "3,,1,\"Jane \\\"\"JJ\\\"\" Doe\",jane@example.com\n",
			$this->readBackup(),
		);
	}
}
