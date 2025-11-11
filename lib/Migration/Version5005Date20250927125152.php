<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

class Version5005Date20250927125152 extends SimpleMigrationStep {

	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$textColumnType = Type::getType(Types::TEXT);

		$mailAccountTable = $schema->getTable('mail_accounts');
		$oauthTokenColumn = $mailAccountTable->getColumn('oauth_refresh_token');
		if ($oauthTokenColumn->getType() !== $textColumnType) {
			$oauthTokenColumn->setType($textColumnType);
		}

		return $schema;
	}
}
