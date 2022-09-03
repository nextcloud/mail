<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna@nextcloud.com>
 *
 * @author 2021 Anna Larch <anna@nextcloud.com>
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

use OCA\Mail\Exception\ValidationException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * @template-extends QBMapper<Provisioning>
 */
class ProvisioningMapper extends QBMapper {
	/** @var LoggerInterface */
	private $logger;

	public function __construct(IDBConnection $db, LoggerInterface $logger) {
		parent::__construct($db, 'mail_provisionings');
		$this->logger = $logger;
	}

	/**
	 * Should return the wildcard domain last!
	 *
	 * @return Provisioning[]
	 */
	public function getAll(): array {
		$qb = $this->db->getQueryBuilder();
		$qb = $qb->select('*')
			->from($this->getTableName())
			->orderBy('provisioning_domain', 'desc');
		try {
			return $this->findEntities($qb);
		} catch (DoesNotExistException $e) {
			$this->logger->error('No provisioning configs available');
			return [];
		}
	}

	/**
	 * @param array $data
	 * @return Provisioning
	 * @throws ValidationException
	 */
	public function validate(array $data): Provisioning {
		$exception = new ValidationException();

		if (!isset($data['provisioningDomain']) || $data['provisioningDomain'] === '') {
			$exception->setField('provisioningDomain', false);
		}
		if (!isset($data['emailTemplate']) || $data['emailTemplate'] === '') {
			$exception->setField('emailTemplate', false);
		}
		if (!isset($data['imapUser']) || $data['imapUser'] === '') {
			$exception->setField('imapUser', false);
		}
		if (!isset($data['imapHost']) || $data['imapHost'] === '') {
			$exception->setField('imapHost', false);
		}
		if (!isset($data['imapPort']) || (int)$data['imapPort'] === 0) {
			$exception->setField('imapHost', false);
		}
		if (!isset($data['imapSslMode']) || $data['imapSslMode'] === '') {
			$exception->setField('imapSslMode', false);
		}
		if (!isset($data['smtpUser']) || $data['smtpUser'] === '') {
			$exception->setField('smtpUser', false);
		}
		if (!isset($data['smtpHost']) || $data['smtpHost'] === '') {
			$exception->setField('smtpHost', false);
		}
		if (!isset($data['smtpPort']) || (int)$data['smtpPort'] === 0) {
			$exception->setField('smtpPort', false);
		}
		if (!isset($data['smtpSslMode']) || $data['smtpSslMode'] === '') {
			$exception->setField('smtpSslMode', false);
		}

		$ldapAliasesProvisioning = (bool)($data['ldapAliasesProvisioning'] ?? false);
		$ldapAliasesAttribute = $data['ldapAliasesAttribute'] ?? '';

		if ($ldapAliasesProvisioning && empty($ldapAliasesAttribute)) {
			$exception->setField('ldapAliasesAttribute', false);
		}

		if (!empty($exception->getFields())) {
			throw $exception;
		}

		$provisioning = new Provisioning();
		$provisioning->setId($data['id'] ?? null);
		$provisioning->setProvisioningDomain($data['provisioningDomain']);
		$provisioning->setEmailTemplate($data['emailTemplate']);
		$provisioning->setImapUser($data['imapUser']);
		$provisioning->setImapHost($data['imapHost']);
		$provisioning->setImapPort((int)$data['imapPort']);
		$provisioning->setImapSslMode($data['imapSslMode']);
		$provisioning->setSmtpUser($data['smtpUser']);
		$provisioning->setSmtpHost($data['smtpHost']);
		$provisioning->setSmtpPort((int)$data['smtpPort']);
		$provisioning->setSmtpSslMode($data['smtpSslMode']);

		$provisioning->setSieveEnabled((bool)$data['sieveEnabled']);
		$provisioning->setSieveHost($data['sieveHost'] ?? '');
		$provisioning->setSieveUser($data['sieveUser'] ?? '');
		$provisioning->setSievePort($data['sievePort'] ?? null);
		$provisioning->setSieveSslMode($data['sieveSslMode'] ?? '');

		$provisioning->setLdapAliasesProvisioning($ldapAliasesProvisioning);
		$provisioning->setLdapAliasesAttribute($ldapAliasesAttribute);

		return $provisioning;
	}

	public function get(int $id): ?Provisioning {
		$qb = $this->db->getQueryBuilder();
		$qb = $qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id), IQueryBuilder::PARAM_INT));
		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException $e) {
			$this->logger->error('Could not find entry with ID #' . $id);
			return null;
		}
	}
}
