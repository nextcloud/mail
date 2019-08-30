<?php declare(strict_types=1);

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
use OCA\Mail\Exception\ServiceException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class MailboxMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_mailboxes');
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
	 * @throws DoesNotExistException
	 */
	public function findSpecial(Account $account, string $specialUse): Mailbox {
		$mailboxes = $this->findAll($account);

		// First, let's try to detect by special use attribute
		foreach ($mailboxes as $mailbox) {
			$specialUses = json_decode($mailbox->getSpecialUse(), true) ?? [];
			if (in_array($specialUse, $specialUses, true)) {
				return $mailbox;
			}
		}

		// No luck so far, let's do another round and just guess
		foreach ($mailboxes as $mailbox) {
			// TODO: also check localized name
			if (strtolower($mailbox->getName()) === strtolower($specialUse)) {
				return $mailbox;
			}
		}

		// Give up
		throw new DoesNotExistException("Special mailbox $specialUse does not exist");
	}

}
