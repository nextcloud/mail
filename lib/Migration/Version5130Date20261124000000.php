<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * The SMTP user used to be ignored for XOAUTH2 and is now the identity sent to the
 * provider. Clear deviating values so existing accounts keep authenticating as their email.
 *
 * @psalm-api
 */
class Version5130Date20261124000000 extends SimpleMigrationStep {
	public function __construct(
		private IDBConnection $db,
	) {
	}

	#[Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$qb = $this->db->getQueryBuilder();
		$qb->update('mail_accounts')
			->set('outbound_user', $qb->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->where($qb->expr()->eq('auth_method', $qb->createNamedParameter('xoauth2')))
			->andWhere($qb->expr()->neq('outbound_user', 'email'));
		$cleared = $qb->executeStatement();

		$output->info("Cleared the SMTP user of $cleared OAuth account(s)");
	}
}
