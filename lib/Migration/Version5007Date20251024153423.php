<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\AppFramework\Services\IAppConfig;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * @psalm-api
 */
class Version5007Date20251024153423 extends SimpleMigrationStep {

	public function __construct(
		private readonly IDBConnection $db,
		private IAppConfig $appConfig,
	) {
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
		$accountsTable = $schema->getTable('mail_accounts');
		if (!$accountsTable->hasColumn('classification_enabled')) {
			$accountsTable->addColumn('classification_enabled', Types::BOOLEAN, [
				'default' => true,
				'notNull' => false,
			]);
		}
		return $schema;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {

		// If value not set or is true, we only care about users who have explicitly disabled classification
		// If classification is enabled by default we care about users who enabled it
		$isEnabledBydefault = $this->appConfig->getAppValueBool('importance_classification_default', true);
		$qb = $this->db->getQueryBuilder();
		$qb->select('userid')
			->from('preferences')
			->where(
				$qb->expr()->andx(
					$qb->expr()->eq('appid', $qb->createNamedParameter('mail')),
					$qb->expr()->eq('configkey', $qb->createNamedParameter('tag-classified-messages')),
					$qb->expr()->eq('configvalue', $qb->createNamedParameter($isEnabledBydefault ? 'false' : 'true'))
				)
			);

		$res = $qb->executeQuery();
		$users = $res->fetchAll(\PDO::FETCH_COLUMN);
		$res->closeCursor();

		$output->info('Migrating classification user preferences to mail_accounts table');
		$output->startProgress();

		// If classification is disabled by default we first disable it for all users
		if (!$isEnabledBydefault) {
			$qb = $this->db->getQueryBuilder();
			$qb->update('mail_accounts')
				->set('classification_enabled', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL));
			$qb->executeStatement();

		}
		// Then if classification is disabled by default, we re-enable it for users who did so previously
		// If classification is enabled by default, we disbale it users who did so previously
		$qb = $this->db->getQueryBuilder();
		$qb->update('mail_accounts')
			->set('classification_enabled', $qb->createNamedParameter(!$isEnabledBydefault, IQueryBuilder::PARAM_BOOL))
			->where($qb->expr()->in('user_id', $qb->createParameter('users')));
		foreach (array_chunk($users, 1000) as $chunk) {
			$output->advance();
			$qb->setParameter('users', $chunk, IQueryBuilder::PARAM_STR_ARRAY);
			$qb->executeStatement();
		}
		$output->finishProgress();
		$output->info('Removing old classification user preferences from preferences table');
		$qb = $this->db->getQueryBuilder();
		$qb->delete('preferences')
			->where(
				$qb->expr()->andx(
					$qb->expr()->eq('appid', $qb->createNamedParameter('mail')),
					$qb->expr()->eq('configkey', $qb->createNamedParameter('tag-classified-messages'))
				)
			);
		$qb->executeStatement();
		$output->info('Removed old classification user preferences from preferences table');
	}
}
