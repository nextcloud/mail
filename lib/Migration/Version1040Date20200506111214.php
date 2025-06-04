<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1040Date20200506111214 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->createTable('mail_classifiers');
		$table->addColumn('id', Types::INTEGER, [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 20,
		]);
		$table->addColumn('account_id', Types::INTEGER, [
			'notnull' => true,
			'length' => 20,
		]);
		$table->addColumn('type', Types::STRING, [
			'notnull' => true,
			'length' => 255,
		]);
		$table->addColumn('estimator', Types::STRING, [
			'notnull' => true,
			'length' => 255,
		]);
		$table->addColumn('app_version', Types::STRING, [
			'notnull' => true,
			'length' => 31,
		]);
		$table->addColumn('training_set_size', Types::INTEGER, [
			'notnull' => true,
			'length' => 4,
		]);
		$table->addColumn('validation_set_size', Types::INTEGER, [
			'notnull' => true,
			'length' => 4,
		]);
		$table->addColumn('recall_important', Types::DECIMAL, [
			'notnull' => true,
			'precision' => 10,
			'scale' => 5,
		]);
		$table->addColumn('precision_important', Types::DECIMAL, [
			'notnull' => true,
			'precision' => 10,
			'scale' => 5,
		]);
		$table->addColumn('f1_score_important', Types::DECIMAL, [
			'notnull' => true,
			'precision' => 10,
			'scale' => 5,
		]);
		$table->addColumn('duration', Types::INTEGER, [
			'notnull' => true,
			'length' => 4,
		]);
		$table->addColumn('active', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$table->addColumn('created_at', Types::INTEGER, [
			'notnull' => true,
			'length' => 4,
		]);

		$table->setPrimaryKey(['id']);
		$table->addIndex(['account_id', 'type'], 'mail_clssfr_accnt_id_type');

		return $schema;
	}
}
