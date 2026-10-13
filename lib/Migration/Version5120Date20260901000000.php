<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * @psalm-api
 */
class Version5120Date20260901000000 extends SimpleMigrationStep {
	private const OLD_INDEX = 'mail_internal_address_uniq';
	private const NEW_INDEX = 'mail_int_addr_uid_addr_uidx';

	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('mail_internal_address')) {
			return null;
		}

		$table = $schema->getTable('mail_internal_address');
		if ($table->hasIndex(self::NEW_INDEX)) {
			return null;
		}

		$table->addUniqueIndex(['user_id', 'address'], self::NEW_INDEX);
		if ($table->hasIndex(self::OLD_INDEX)) {
			$table->dropIndex(self::OLD_INDEX);
		}

		return $schema;
	}
}
