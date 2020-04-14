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

use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ICrypto;

class Manager {

	/** @var IUserManager */
	private $userManager;

	/** @var ConfigMapper */
	private $configMapper;

	/** @var MailAccountMapper */
	private $mailAccountMapper;

	/** @var ICrypto */
	private $crypto;

	/** @var ILogger */
	private $logger;

	public function __construct(IUserManager $userManager,
								ConfigMapper $configMapper,
								MailAccountMapper $mailAccountMapper,
								ICrypto $crypto,
								ILogger $logger) {
		$this->userManager = $userManager;
		$this->configMapper = $configMapper;
		$this->mailAccountMapper = $mailAccountMapper;
		$this->crypto = $crypto;
		$this->logger = $logger;
	}

	public function getConfig(): ?Config {
		return $this->configMapper->load();
	}

	public function provision(Config $config): int {
		$cnt = 0;
		$this->userManager->callForAllUsers(function (IUser $user) use ($config, &$cnt) {
			$this->provisionSingleUser($config, $user);
			$cnt++;
		});
		return $cnt;
	}

	public function provisionSingleUser(Config $config, IUser $user): void {
		try {
			$existing = $this->mailAccountMapper->findProvisionedAccount($user);

			$this->mailAccountMapper->update(
				$this->updateAccount($user, $existing, $config)
			);
		} catch (DoesNotExistException $e) {
			// Fine, then we create a new one
			$new = new MailAccount();
			$new->setUserId($user->getUID());
			$new->setProvisioned(true);

			$this->mailAccountMapper->insert(
				$this->updateAccount($user, $new, $config)
			);
		}
	}

	public function newProvisioning(string $email,
									string $imapUser,
									string $imapHost,
									int $imapPort,
									string $imapSslMode,
									string $smtpUser,
									string $smtpHost,
									int $smtpPort,
									string $smtpSslMode): void {
		$config = $this->configMapper->save(new Config([
			'active' => true,
			'email' => $email,
			'imapUser' => $imapUser,
			'imapHost' => $imapHost,
			'imapPort' => $imapPort,
			'imapSslMode' => $imapSslMode,
			'smtpUser' => $smtpUser,
			'smtpHost' => $smtpHost,
			'smtpPort' => $smtpPort,
			'smtpSslMode' => $smtpSslMode,
		]));

		$this->provision($config);
	}

	private function updateAccount(IUser $user, MailAccount $account, Config $config): MailAccount {
		$account->setEmail($config->buildEmail($user));
		if ($user->getDisplayName() !== $user->getUID()) {
			// Only set if it's something meaningful
			$account->setName($user->getDisplayName());
		}
		$account->setInboundUser($config->buildImapUser($user));
		$account->setInboundHost($config->getImapHost());
		$account->setInboundPort($config->getImapPort());
		$account->setInboundSslMode($config->getImapSslMode());
		$account->setOutboundUser($config->buildSmtpUser($user));
		$account->setOutboundHost($config->getSmtpHost());
		$account->setOutboundPort($config->getSmtpPort());
		$account->setOutboundSslMode($config->getSmtpSslMode());

		return $account;
	}

	public function deprovision(): void {
		$this->mailAccountMapper->deleteProvisionedAccounts();

		$config = $this->configMapper->load();
		if ($config !== null) {
			$config->setActive(false);
			$this->configMapper->save($config);
		}
	}

	public function importConfig(array $data): Config {
		if (!isset($data['imapUser'])) {
			$data['imapUser'] = $data['email'];
		}
		if (!isset($data['smtpUser'])) {
			$data['smtpUser'] = $data['email'];
		}
		$data['active'] = true;

		return $this->configMapper->save(new Config($data));
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
}
