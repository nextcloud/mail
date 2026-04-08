<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use Doctrine\DBAL\Schema\Table;
use OCP\DB\Exception;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\Attributes\ModifyColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;
use Psr\Log\LoggerInterface;

#[ModifyColumn(
	table: 'mail_accounts',
	description: 'Remove invalid mailbox_id from *_mailbox_id columns and add foreign keys to ensure consistency')
]
/**
 * @psalm-api
 */
class Version5007Date20260108124422 extends SimpleMigrationStep {
	public function __construct(
		private IDBConnection $db,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @param string $mailboxType
	 */
	private function removeInconsistentMailboxEntries(string $mailboxType): void {
		$selectQueryBuilder = $this->db->getQueryBuilder();

		$selectQueryBuilder->select('accounts.id')
			->from('mail_accounts', 'accounts')
			->leftJoin(
				'accounts',
				'mail_mailboxes',
				'mailboxes',
				$selectQueryBuilder->expr()->eq(
					"accounts.{$mailboxType}_mailbox_id",
					'mailboxes.id',
					IQueryBuilder::PARAM_INT
				)
			)
			->where($selectQueryBuilder->expr()->isNull('mailboxes.id'))
			->andWhere($selectQueryBuilder->expr()->isNotNull("accounts.{$mailboxType}_mailbox_id"));

		try {
			$affectedRows = $selectQueryBuilder->executeQuery()->fetchAll();

			foreach ($affectedRows as $row) {
				$updateQueryBuilder = $this->db->getQueryBuilder();
				$updateQueryBuilder->update('mail_accounts')
					->set(
						"{$mailboxType}_mailbox_id",
						$updateQueryBuilder->createNamedParameter(null, IQueryBuilder::PARAM_NULL)
					)
					->where(
						$updateQueryBuilder->expr()->eq(
							'id',
							$updateQueryBuilder->createNamedParameter($row['id'], IQueryBuilder::PARAM_INT)
						)
					);
				$updateQueryBuilder->executeStatement();
			}
		} catch (Exception $e) {
			$this->logger->error("Emptying inconsistent {$mailboxType}_mailbox_id field failed", [
				'exception' => $e
			]);
		}
	}

	/**
	 * @param Table $accountsTable
	 * @param Table $mailboxesTable
	 * @param string $mailboxType
	 * @return void
	 */
	private function addMailboxKey(Table $accountsTable, Table $mailboxesTable, string $mailboxType): void {
		$accountsTable->addForeignKeyConstraint(
			$mailboxesTable,
			["{$mailboxType}_mailbox_id"],
			['id'],
			[
				'onDelete' => 'SET NULL',
			],
		);
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	#[Override]
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$this->removeInconsistentMailboxEntries('drafts');
		$this->removeInconsistentMailboxEntries('sent');
		$this->removeInconsistentMailboxEntries('trash');
		$this->removeInconsistentMailboxEntries('junk');
		$this->removeInconsistentMailboxEntries('archive');
		$this->removeInconsistentMailboxEntries('snooze');
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if ($schema->hasTable('mail_accounts')) {
			$accountsTable = $schema->getTable('mail_accounts');
			$mailboxesTable = $schema->getTable('mail_mailboxes');

			$this->addMailboxKey($accountsTable, $mailboxesTable, 'drafts');
			$this->addMailboxKey($accountsTable, $mailboxesTable, 'sent');
			$this->addMailboxKey($accountsTable, $mailboxesTable, 'trash');
			$this->addMailboxKey($accountsTable, $mailboxesTable, 'junk');
			$this->addMailboxKey($accountsTable, $mailboxesTable, 'archive');
			$this->addMailboxKey($accountsTable, $mailboxesTable, 'snooze');
		}

		return $schema;
	}
}
