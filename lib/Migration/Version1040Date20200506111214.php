<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
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
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->createTable('mail_classifiers');
		$table->addColumn('id', 'integer', [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 20,
		]);
		$table->addColumn('account_id', 'integer', [
			'notnull' => true,
			'length' => 20,
		]);
		$table->addColumn('type', 'string', [
			'notnull' => true,
			'length' => 255,
		]);
		$table->addColumn('estimator', 'string', [
			'notnull' => true,
			'length' => 255,
		]);
		$table->addColumn('app_version', 'string', [
			'notnull' => true,
			'length' => 31,
		]);
		$table->addColumn('training_set_size', 'integer', [
			'notnull' => true,
			'length' => 4,
		]);
		$table->addColumn('validation_set_size', 'integer', [
			'notnull' => true,
			'length' => 4,
		]);
		$table->addColumn('recall_important', 'decimal', [
			'notnull' => true,
			'precision' => 10,
			'scale' => 5,
		]);
		$table->addColumn('precision_important', 'decimal', [
			'notnull' => true,
			'precision' => 10,
			'scale' => 5,
		]);
		$table->addColumn('f1_score_important', 'decimal', [
			'notnull' => true,
			'precision' => 10,
			'scale' => 5,
		]);
		$table->addColumn('duration', 'integer', [
			'notnull' => true,
			'length' => 4,
		]);
		$table->addColumn('active', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$table->addColumn('created_at', 'integer', [
			'notnull' => true,
			'length' => 4,
		]);

		$table->setPrimaryKey(['id']);
		$table->addIndex(['account_id', 'type'], 'mail_clssfr_accnt_id_type');

		return $schema;
	}
}
