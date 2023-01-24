<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Db;

use OCA\Mail\Account;
use OCA\Mail\Exception\MailboxLockedException;
use OCA\Mail\Exception\ServiceException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;
use OCP\IDBConnection;
use function array_map;

/**
 * @template-extends QBMapper<Mailbox>
 */
class MailboxMapper extends QBMapper {
	/** @var ITimeFactory */
	private $timeFactory;

	public function __construct(IDBConnection $db,
								ITimeFactory $timeFactory) {
		parent::__construct($db, 'mail_mailboxes');
		$this->timeFactory = $timeFactory;
	}

	/**
	 * @param Account $account
	 *
	 * @return Mailbox[]
	 */
	public function findAll(Account $account): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('account_id', $qb->createNamedParameter($account->getId())));

		return $this->findEntities($select);
	}

	/**
	 * @return \Generator<int>
	 */
	public function findAllIds(): \Generator {
		$qb = $this->db->getQueryBuilder();
		$qb->select('id')
			->from($this->getTableName());

		$cursor = $qb->execute();
		while ($row = $cursor->fetch()) {
			yield (int)$row['id'];
		}
		$cursor->closeCursor();
	}

	/**
	 * @throws DoesNotExistException
	 * @throws ServiceException
	 */
	public function find(Account $account, string $name): Mailbox {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('account_id', $qb->createNamedParameter($account->getId())),
				$qb->expr()->eq('name', $qb->createNamedParameter($name))
			);

		try {
			return $this->findEntity($select);
		} catch (MultipleObjectsReturnedException $e) {
			// Not possible due to DB constraints
			throw new ServiceException("The impossible has happened", 42, $e);
		}
	}

	/**
	 * @param int $id
	 * @param string $uid
	 *
	 * @return Mailbox
	 *
	 * @throws DoesNotExistException
	 * @throws ServiceException
	 */
	public function findById(int $id): Mailbox {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT)
			);

		try {
			return $this->findEntity($select);
		} catch (MultipleObjectsReturnedException $e) {
			// Not possible due to DB constraints
			throw new ServiceException("The impossible has happened", 42, $e);
		}
	}

	/**
	 * @return Mailbox[]
	 *
	 * @throws Exception
	 */
	public function findByIds(array $ids): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->in('id', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY)
			);
		return $this->findEntities($select);
	}


	/**
	 * @param int $id
	 * @param string $uid
	 *
	 * @return Mailbox
	 *
	 * @throws DoesNotExistException
	 * @throws ServiceException
	 */
	public function findByUid(int $id, string $uid): Mailbox {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select('mb.*')
			->from($this->getTableName(), 'mb')
			->join('mb', 'mail_accounts', 'a', $qb->expr()->eq('mb.account_id', 'a.id', IQueryBuilder::PARAM_INT))
			->where(
				$qb->expr()->eq('a.user_id', $qb->createNamedParameter($uid)),
				$qb->expr()->eq('mb.id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT)
			);

		try {
			return $this->findEntity($select);
		} catch (MultipleObjectsReturnedException $e) {
			// Not possible due to DB constraints
			throw new ServiceException("The impossible has happened", 42, $e);
		}
	}

	/**
	 * @throws MailboxLockedException
	 */
	private function lockForSync(Mailbox $mailbox, string $attr, ?int $lock): int {
		$now = $this->timeFactory->getTime();

		if ($lock !== null
			&& $lock > ($now - Mailbox::LOCK_TIMEOUT)) {
			// Another process is syncing
			throw MailboxLockedException::from($mailbox);
		}

		$query = $this->db->getQueryBuilder();
		$query->update($this->getTableName())
			->set($attr, $query->createNamedParameter($now, IQueryBuilder::PARAM_INT))
			->where(
				$query->expr()->eq('id', $query->createNamedParameter($mailbox->getId(), IQueryBuilder::PARAM_INT)),
				$this->eqOrNull($query, $attr, $lock, IQueryBuilder::PARAM_INT)
			);
		if ($query->execute() === 0) {
			// Another process just started syncing

			throw MailboxLockedException::from($mailbox);
		}

		return $now;
	}

	/**
	 * @throws MailboxLockedException
	 */
	public function lockForNewSync(Mailbox $mailbox): void {
		$mailbox->setSyncNewLock(
			$this->lockForSync($mailbox, 'sync_new_lock', $mailbox->getSyncNewLock())
		);
	}

	/**
	 * @throws MailboxLockedException
	 */
	public function lockForChangeSync(Mailbox $mailbox): void {
		$mailbox->setSyncChangedLock(
			$this->lockForSync($mailbox, 'sync_changed_lock', $mailbox->getSyncChangedLock())
		);
	}

	/**
	 * @throws MailboxLockedException
	 */
	public function lockForVanishedSync(Mailbox $mailbox): void {
		$mailbox->setSyncVanishedLock(
			$this->lockForSync($mailbox, 'sync_vanished_lock', $mailbox->getSyncVanishedLock())
		);
	}

	/**
	 * @param Mailbox $mailbox
	 * @param IQueryBuilder $query
	 * @param int|null $value
	 *
	 * @return string|IQueryFunction
	 */
	private function eqOrNull(IQueryBuilder $query, string $column, ?int $value, int $type) {
		if ($value === null) {
			return $query->expr()->isNull($column);
		}
		return $query->expr()->eq($column, $query->createNamedParameter($value, $type));
	}

	public function unlockFromNewSync(Mailbox $mailbox): void {
		$mailbox->setSyncNewLock(null);

		$this->update($mailbox);
	}

	public function unlockFromChangedSync(Mailbox $mailbox): void {
		$mailbox->setSyncChangedLock(null);

		$this->update($mailbox);
	}

	public function unlockFromVanishedSync(Mailbox $mailbox): void {
		$mailbox->setSyncVanishedLock(null);

		$this->update($mailbox);
	}

	public function deleteOrphans(): void {
		$qb1 = $this->db->getQueryBuilder();
		$idsQuery = $qb1->select('m.id')
			->from($this->getTableName(), 'm')
			->leftJoin('m', 'mail_accounts', 'a', $qb1->expr()->eq('m.account_id', 'a.id'))
			->where($qb1->expr()->isNull('a.id'));
		$result = $idsQuery->execute();
		$ids = array_map(static function (array $row) {
			return (int)$row['id'];
		}, $result->fetchAll());
		$result->closeCursor();

		$qb2 = $this->db->getQueryBuilder();
		$qb2->delete($this->getTableName())
			->where($qb2->expr()->in('id', $qb2->createParameter('ids'), IQueryBuilder::PARAM_INT_ARRAY));
		foreach (array_chunk($ids, 1000) as $chunk) {
			$query = $qb2->setParameter('ids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$query->execute();
		}
	}

	/**
	 * Get all UIDS for mail_messages.flag_important = true
	 *
	 * @return int[]
	 */
	public function findFlaggedImportantUids(int $mailboxId) : array {
		$qb = $this->db->getQueryBuilder();
		$query = $qb->select('uid')
				->from('mail_messages')
				->where(
					$qb->expr()->eq('mailbox_id', $qb->createNamedParameter($mailboxId)),
					$qb->expr()->eq('flag_important', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL))
				);

		$cursor = $query->execute();
		$uids = array_map(static function (array $row) {
			return (int)$row['uid'];
		}, $cursor->fetchAll());
		$cursor->closeCursor();

		return $uids;
	}
}
