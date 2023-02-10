<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Service\Provisioning;

use Horde_Mail_Rfc822_Address;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\AliasMapper;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\Provisioning;
use OCA\Mail\Db\ProvisioningMapper;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Exception\ValidationException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\ICacheFactory;
use OCP\IUser;
use OCP\IUserManager;
use OCP\LDAP\ILDAPProvider;
use OCP\LDAP\ILDAPProviderFactory;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

class Manager {
	/** @var IUserManager */
	private $userManager;

	/** @var ProvisioningMapper */
	private $provisioningMapper;

	/** @var MailAccountMapper */
	private $mailAccountMapper;

	/** @var ICrypto */
	private $crypto;

	/** @var ILDAPProviderFactory */
	private $ldapProviderFactory;

	/** @var AliasMapper */
	private $aliasMapper;

	/** @var LoggerInterface */
	private $logger;

	/** @var TagMapper */
	private $tagMapper;

	/** @var ICacheFactory */
	private $cacheFactory;

	public function __construct(IUserManager $userManager,
								ProvisioningMapper $provisioningMapper,
								MailAccountMapper $mailAccountMapper,
								ICrypto $crypto,
								ILDAPProviderFactory $ldapProviderFactory,
								AliasMapper $aliasMapper,
								LoggerInterface $logger,
								TagMapper $tagMapper,
								ICacheFactory $cacheFactory) {
		$this->userManager = $userManager;
		$this->provisioningMapper = $provisioningMapper;
		$this->mailAccountMapper = $mailAccountMapper;
		$this->crypto = $crypto;
		$this->ldapProviderFactory = $ldapProviderFactory;
		$this->aliasMapper = $aliasMapper;
		$this->logger = $logger;
		$this->tagMapper = $tagMapper;
		$this->cacheFactory = $cacheFactory;
	}

	public function getConfigById(int $provisioningId): ?Provisioning {
		return $this->provisioningMapper->get($provisioningId);
	}

	public function getConfigs(): array {
		$cache = null;
		if ($this->cacheFactory->isLocalCacheAvailable()) {
			$cache = $this->cacheFactory->createLocal('provisionings');
			$cached = $cache->get('provisionings_all');
			if ($cached !== null) {
				return unserialize($cached, ['allowed_classes' => [Provisioning::class]]);
			}
		}

		$provisionings = $this->provisioningMapper->getAll();
		// let's cache the provisionings for 5 minutes
		if ($this->cacheFactory->isLocalCacheAvailable()) {
			$cache->set('provisionings_all', serialize($provisionings), 60 * 5);
		}
		return $provisionings;
	}

	public function provision(): int {
		$cnt = 0;
		$configs = $this->getConfigs();
		$this->userManager->callForAllUsers(function (IUser $user) use ($configs, &$cnt) {
			if ($this->provisionSingleUser($configs, $user) === true) {
				$cnt++;
			}
		});
		return $cnt;
	}

	/**
	 * Delete orphaned aliases for the given account.
	 *
	 * A alias is orphaned if not listed in newAliases anymore
	 * (=> the provisioning configuration does contain it anymore)
	 *
	 * @throws \OCP\DB\Exception
	 */
	private function deleteOrphanedAliases(string $userId, int $accountId, array $newAliases): void {
		$existingAliases = $this->aliasMapper->findAll($accountId, $userId);
		foreach ($existingAliases as $existingAlias) {
			if (!in_array($existingAlias->getAlias(), $newAliases, true)) {
				$this->aliasMapper->delete($existingAlias);
			}
		}
	}

	/**
	 * Create new aliases for the given account.
	 *
	 * @throws \OCP\DB\Exception
	 */
	private function createNewAliases(string $userId, int $accountId, array $newAliases, string $displayName, string $accountEmail): void {
		foreach ($newAliases as $newAlias) {
			if ($newAlias === $accountEmail) {
				continue; // skip alias when identical to account email
			}

			try {
				$this->aliasMapper->findByAlias($newAlias, $userId);
			} catch (DoesNotExistException $e) {
				$alias = new Alias();
				$alias->setAccountId($accountId);
				$alias->setName($displayName);
				$alias->setAlias($newAlias);
				$this->aliasMapper->insert($alias);
			}
		}
	}

	/**
	 * @throws \Exception if user id was not found in LDAP
	 *
	 * @TODO: Remove psalm-suppress once Mail requires Nextcloud 22 or above
	 */
	public function ldapAliasesIntegration(Provisioning $provisioning, IUser $user): Provisioning {
		if ($user->getBackendClassName() !== 'LDAP' || $provisioning->getLdapAliasesProvisioning() === false || empty($provisioning->getLdapAliasesAttribute())) {
			return $provisioning;
		}

		/** @psalm-suppress UndefinedInterfaceMethod */
		if ($this->ldapProviderFactory->isAvailable() === false) {
			$this->logger->debug('Request to provision mail aliases but LDAP not available');
			return $provisioning;
		}

		$ldapProvider = $this->ldapProviderFactory->getLDAPProvider();
		/** @psalm-suppress UndefinedInterfaceMethod */
		$provisioning->setAliases($ldapProvider->getMultiValueUserAttribute($user->getUID(), $provisioning->getLdapAliasesAttribute()));

		return $provisioning;
	}

	/**
	 * @param Provisioning[] $provisionings
	 */
	public function provisionSingleUser(array $provisionings, IUser $user): bool {
		$provisioning = $this->findMatchingConfig($provisionings, $user);

		if ($provisioning === null) {
			return false;
		}

		try {
			// TODO: match by UID only, catch multiple objects returned below and delete all those accounts
			$mailAccount = $this->mailAccountMapper->findProvisionedAccount($user);

			$mailAccount = $this->mailAccountMapper->update(
				$this->updateAccount($user, $mailAccount, $provisioning)
			);
		} catch (DoesNotExistException | MultipleObjectsReturnedException $e) {
			if ($e instanceof MultipleObjectsReturnedException) {
				// This is unlikely to happen but not impossible.
				// Let's wipe any existing accounts and start fresh
				$this->aliasMapper->deleteProvisionedAliasesByUid($user->getUID());
				$this->mailAccountMapper->deleteProvisionedAccountsByUid($user->getUID());
			}

			// Fine, then we create a new one
			$mailAccount = new MailAccount();
			$mailAccount->setUserId($user->getUID());

			$mailAccount = $this->mailAccountMapper->insert(
				$this->updateAccount($user, $mailAccount, $provisioning)
			);

			$this->tagMapper->createDefaultTags($mailAccount);
		}

		// @TODO: Remove method_exists once Mail requires Nextcloud 22 or above
		if (method_exists(ILDAPProvider::class, 'getMultiValueUserAttribute')) {
			try {
				$provisioning = $this->ldapAliasesIntegration($provisioning, $user);
			} catch (\Throwable $e) {
				$this->logger->warning('Request to provision mail aliases failed', ['exception' => $e]);
				// return here to avoid provisioning of aliases.
				return true;
			}

			try {
				$this->deleteOrphanedAliases($user->getUID(), $mailAccount->getId(), $provisioning->getAliases());
			} catch (\Throwable $e) {
				$this->logger->warning('Deleting orphaned aliases failed', ['exception' => $e]);
			}

			try {
				$this->createNewAliases($user->getUID(), $mailAccount->getId(), $provisioning->getAliases(), $user->getDisplayName(), $mailAccount->getEmail());
			} catch (\Throwable $e) {
				$this->logger->warning('Creating new aliases failed', ['exception' => $e]);
			}
		}

		return true;
	}

	/**
	 * @throws ValidationException
	 * @throws \OCP\DB\Exception
	 */
	public function newProvisioning(array $data): Provisioning {
		$provisioning = $this->provisioningMapper->validate($data);
		return $this->provisioningMapper->insert($provisioning);
	}

	/**
	 * @throws ValidationException
	 * @throws \OCP\DB\Exception
	 */
	public function updateProvisioning(array $data): void {
		$provisioning = $this->provisioningMapper->validate($data);
		$this->provisioningMapper->update($provisioning);
	}

	private function updateAccount(IUser $user, MailAccount $account, Provisioning $config): MailAccount {
		// Set the ID to make sure it reflects when the account switches from one config to another
		$account->setProvisioningId($config->getId());

		$account->setEmail($config->buildEmail($user));
		$account->setName($user->getDisplayName());
		$account->setInboundUser($config->buildImapUser($user));
		$account->setInboundHost($config->getImapHost());
		$account->setInboundPort($config->getImapPort());
		$account->setInboundSslMode($config->getImapSslMode());
		$account->setOutboundUser($config->buildSmtpUser($user));
		$account->setOutboundHost($config->getSmtpHost());
		$account->setOutboundPort($config->getSmtpPort());
		$account->setOutboundSslMode($config->getSmtpSslMode());
		$account->setSieveEnabled($config->getSieveEnabled());

		if ($config->getSieveEnabled()) {
			$account->setSieveUser($config->buildSieveUser($user));
			$account->setSieveHost($config->getSieveHost());
			$account->setSievePort($config->getSievePort());
			$account->setSieveSslMode($config->getSieveSslMode());
		} else {
			$account->setSieveUser(null);
			$account->setSieveHost(null);
			$account->setSievePort(null);
			$account->setSieveSslMode(null);
		}

		return $account;
	}

	public function deprovision(Provisioning $provisioning): void {
		$this->mailAccountMapper->deleteProvisionedAccounts($provisioning->getId());
		$this->provisioningMapper->delete($provisioning);
	}

	public function updatePassword(IUser $user, string $password): void {
		try {
			$account = $this->mailAccountMapper->findProvisionedAccount($user);

			if (!empty($account->getInboundPassword())
				&& $this->crypto->decrypt($account->getInboundPassword()) === $password
				&& !empty($account->getOutboundPassword())
				&& $this->crypto->decrypt($account->getOutboundPassword()) === $password) {
				$this->logger->debug('Password of provisioned account is up to date');
				return;
			}

			$account->setInboundPassword($this->crypto->encrypt($password));
			$account->setOutboundPassword($this->crypto->encrypt($password));
			$this->mailAccountMapper->update($account);

			$this->logger->debug('Provisioned account password udpated');
		} catch (DoesNotExistException $e) {
			// Nothing to update
		}
	}

	/**
	 * @param Provisioning[] $provisionings
	 */
	private function findMatchingConfig(array $provisionings, IUser $user): ?Provisioning {
		foreach ($provisionings as $provisioning) {
			if ($provisioning->getProvisioningDomain() === Provisioning::WILDCARD) {
				return $provisioning;
			}

			$email = $user->getEMailAddress();
			if ($email === null) {
				continue;
			}
			$rfc822Address = new Horde_Mail_Rfc822_Address($email);
			if ($rfc822Address->matchDomain($provisioning->getProvisioningDomain())) {
				return $provisioning;
			}
		}

		return null;
	}
}
