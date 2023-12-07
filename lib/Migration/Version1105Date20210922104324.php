<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

class Version1105Date20210922104324 extends SimpleMigrationStep {
	private $connection;
	private $logger;

	public function __construct(IDBConnection $connection, LoggerInterface $logger) {
		$this->connection = $connection;
		$this->logger = $logger;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('accounts.id')
			->from('mail_accounts', 'accounts')
			->leftJoin('accounts', 'mail_provisionings', 'provisionings', $qb->expr()->eq('accounts.provisioning_id', 'provisionings.id'))
			->where($qb->expr()->isNotNull('accounts.provisioning_id'))
			->andWhere($qb->expr()->isNull('provisionings.id'));

		try {
			$result = $qb->executeQuery();
		} catch (Exception $e) {
			$this->logger->info('Migration to cleanup mail accounts without valid provisioning configuration failed', [
				'exception' => $e
			]);
			return;
		}

		$accountIds = array_map(static function ($row) {
			return (int)$row['id'];
		}, $result->fetchAll());
		$result->closeCursor();

		if (count($accountIds) === 0) {
			return;
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('mail_accounts')
			->where($qb->expr()->in('id', $qb->createNamedParameter($accountIds, IQueryBuilder::PARAM_INT_ARRAY)));

		try {
			$qb->executeStatement();
		} catch (Exception $e) {
			$this->logger->info('Migration to cleanup mail accounts without valid provisioning configuration failed', [
				'exception' => $e
			]);
			return;
		}
	}
}
