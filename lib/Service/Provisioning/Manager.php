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
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\Provisioning;
use OCA\Mail\Db\ProvisioningMapper;
use OCA\Mail\Exception\ValidationException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IUser;
use OCP\IUserManager;
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

	/** @var LoggerInterface */
	private $logger;

	public function __construct(IUserManager $userManager,
								ProvisioningMapper $provisioningMapper,
								MailAccountMapper $mailAccountMapper,
								ICrypto $crypto,
								LoggerInterface $logger) {
		$this->userManager = $userManager;
		$this->provisioningMapper = $provisioningMapper;
		$this->mailAccountMapper = $mailAccountMapper;
		$this->crypto = $crypto;
		$this->logger = $logger;
	}

	public function getConfigById(int $provisioningId): ?Provisioning {
		return $this->provisioningMapper->get($provisioningId);
	}

	public function getConfigs(): array {
		return $this->provisioningMapper->getAll();
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
	 * @param Provisioning[] $provisionings
	 */
	public function provisionSingleUser(array $provisionings, IUser $user): bool {
		$provisioning = $this->findMatchingConfig($provisionings, $user);

		if ($provisioning === null) {
			return false;
		}

		try {
			// TODO: match by UID only, catch multiple objects returned below and delete all those accounts
			$existing = $this->mailAccountMapper->findProvisionedAccount($user);

			$this->mailAccountMapper->update(
				$this->updateAccount($user, $existing, $provisioning)
			);
			return true;
		} catch (DoesNotExistException $e) {
			// Fine, then we create a new one
			$new = new MailAccount();
			$new->setUserId($user->getUID());

			$this->mailAccountMapper->insert(
				$this->updateAccount($user, $new, $provisioning)
			);
			return true;
		} catch (MultipleObjectsReturnedException $e) {
			// This is unlikely to happen but not impossible.
			// Let's wipe any existing accounts and start fresh
			$this->mailAccountMapper->deleteProvisionedAccountsByUid($user->getUID());

			$new = new MailAccount();
			$new->setUserId($user->getUID());

			$this->mailAccountMapper->insert(
				$this->updateAccount($user, $new, $provisioning)
			);
			return true;
		}
		return false;
	}

	public function newProvisioning(array $data): void {
		try {
			$provisioning = $this->provisioningMapper->validate(
				$data
			);
		} catch (ValidationException $e) {
			throw $e;
		}
		$this->provisioningMapper->insert($provisioning);
	}

	public function updateProvisioning(array $data): void {
		try {
			$provisioning = $this->provisioningMapper->validate(
				$data
			);
		} catch (ValidationException $e) {
			throw $e;
		}

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
