<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCA\Mail\Exception\ValidationException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<OidcProvider>
 */
class OidcProviderMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_oidc_providers');
	}

	/**
	 * @return OidcProvider[]
	 */
	public function getAll(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->orderBy('email_domain', 'asc');
		return $this->findEntities($qb);
	}

	public function get(int $id): ?OidcProvider {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException $e) {
			return null;
		}
	}

	/**
	 * Find the provider responsible for the given email domain (case-insensitive), if any.
	 */
	public function findByEmailDomain(string $emailDomain): ?OidcProvider {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq(
				$qb->func()->lower('email_domain'),
				$qb->createNamedParameter(mb_strtolower($emailDomain)),
			));
		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException $e) {
			return null;
		}
	}

	/**
	 * Build (but do not persist) an OidcProvider from admin form data.
	 *
	 * @throws ValidationException
	 */
	public function validate(array $data): OidcProvider {
		$exception = new ValidationException();

		$requiredStrings = [
			'name', 'emailDomain', 'imapHost', 'imapSslMode',
			'smtpHost', 'smtpSslMode', 'clientId',
		];
		foreach ($requiredStrings as $field) {
			if (!isset($data[$field]) || $data[$field] === '') {
				$exception->setField($field, false);
			}
		}
		if (!isset($data['imapPort']) || (int)$data['imapPort'] === 0) {
			$exception->setField('imapPort', false);
		}
		if (!isset($data['smtpPort']) || (int)$data['smtpPort'] === 0) {
			$exception->setField('smtpPort', false);
		}

		// Endpoints come either from the discovery document or are entered manually.
		$manualEndpoints = !empty($data['manualEndpoints']);
		if ($manualEndpoints) {
			foreach (['authorizationEndpoint', 'tokenEndpoint'] as $field) {
				if (!isset($data[$field]) || $data[$field] === '') {
					$exception->setField($field, false);
				}
			}
		} elseif (!isset($data['discoveryUrl']) || $data['discoveryUrl'] === '') {
			$exception->setField('discoveryUrl', false);
		}

		if (!empty($exception->getFields())) {
			throw $exception;
		}

		$provider = new OidcProvider();
		if (isset($data['id'])) {
			$provider->setId((int)$data['id']);
		}
		$provider->setName($data['name']);
		$provider->setEmailDomain($data['emailDomain']);
		$provider->setImapHost($data['imapHost']);
		$provider->setImapPort((int)$data['imapPort']);
		$provider->setImapSslMode($data['imapSslMode']);
		$provider->setSmtpHost($data['smtpHost']);
		$provider->setSmtpPort((int)$data['smtpPort']);
		$provider->setSmtpSslMode($data['smtpSslMode']);
		$provider->setClientId($data['clientId']);
		$provider->setManualEndpoints($manualEndpoints);
		$provider->setDiscoveryUrl($manualEndpoints ? '' : $data['discoveryUrl']);
		$provider->setAuthorizationEndpoint($manualEndpoints ? $data['authorizationEndpoint'] : '');
		$provider->setTokenEndpoint($manualEndpoints ? $data['tokenEndpoint'] : '');
		// Optional even in manual mode: without it a rejected refresh can not be
		// confirmed, so it is simply never treated as a dead grant.
		$provider->setIntrospectionEndpoint(
			$manualEndpoints ? ($data['introspectionEndpoint'] ?? '') : '',
		);
		$provider->setScope(
			isset($data['scope']) && $data['scope'] !== ''
				? $data['scope']
				: 'openid email offline_access',
		);
		// Only overwrite the secret when a real value (not the masked placeholder) is given
		if (isset($data['clientSecret']) && $data['clientSecret'] !== OidcProvider::CLIENT_SECRET_PLACEHOLDER) {
			$provider->setClientSecret($data['clientSecret']);
		}

		return $provider;
	}
}
