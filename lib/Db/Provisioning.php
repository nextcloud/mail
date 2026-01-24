<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use OCP\IUser;
use ReturnTypeWillChange;

/**
 * @method string getProvisioningDomain()
 * @method void setProvisioningDomain(string $provisioningDomain)
 * @method string getEmailTemplate()
 * @method void setEmailTemplate(string $emailTemplate)
 * @method string getImapUser()
 * @method void setImapUser(string $imapUser)
 * @method string getImapHost()
 * @method void setImapHost(string $imapHost)
 * @method int getImapPort()
 * @method void setImapPort(int $imapPort)
 * @method string getImapSslMode()
 * @method void setImapSslMode(string $imapSslMode)
 * @method string getSmtpUser()
 * @method void setSmtpUser(string $smtpUser)
 * @method string getSmtpHost()
 * @method void setSmtpHost(string $smtpHost)
 * @method int getSmtpPort()
 * @method void setSmtpPort(int $smtpPort)
 * @method string getSmtpSslMode()
 * @method void setSmtpSslMode(string $smtpSslMode)
 * @method bool|null getMasterPasswordEnabled()
 * @method void setMasterPasswordEnabled(bool $masterPasswordEnabled)
 * @method string|null getMasterPassword()
 * @method void setMasterPassword(?string $masterPassword)
 * @method string|null getMasterUser()
 * @method void setMasterUser(?string $masterUser)
 * @method bool|null getSieveEnabled()
 * @method void setSieveEnabled(bool $sieveEnabled)
 * @method string|null getSieveHost()
 * @method void setSieveHost(?string $sieveHost)
 * @method int|null getSievePort()
 * @method void setSievePort(?int $sievePort)
 * @method string|null getSieveSslMode()
 * @method void setSieveSslMode(?string $sieveSslMode)
 * @method string|null getSieveUser()
 * @method void setSieveUser(?string $sieveUser)
 * @method array getAliases()
 * @method void setAliases(array $aliases)
 * @method bool getLdapAliasesProvisioning()
 * @method void setLdapAliasesProvisioning(bool $ldapAliasesProvisioning)
 * @method string|null getLdapAliasesAttribute()
 * @method void setLdapAliasesAttribute(?string $ldapAliasesAttribute)
 */
class Provisioning extends Entity implements JsonSerializable {
	public const WILDCARD = '*';
	public const MASTER_PASSWORD_PLACEHOLDER = '********';

	protected $provisioningDomain;
	protected $emailTemplate;
	protected $imapUser;
	protected $imapHost;
	protected $imapPort;
	protected $imapSslMode;
	protected $smtpUser;
	protected $smtpHost;
	protected $smtpPort;
	protected $smtpSslMode;
	protected $masterPasswordEnabled;
	protected $masterPassword;
	protected $masterUser;
	protected $sieveEnabled;
	protected $sieveUser;
	protected $sieveHost;
	protected $sievePort;
	protected $sieveSslMode;
	protected $aliases = [];
	protected $ldapAliasesProvisioning;
	protected $ldapAliasesAttribute;

	public function __construct() {
		$this->addType('imapPort', 'integer');
		$this->addType('smtpPort', 'integer');
		$this->addType('masterPasswordEnabled', 'boolean');
		$this->addType('masterPassword', 'string');
		$this->addType('masterUser', 'string');
		$this->addType('sieveEnabled', 'boolean');
		$this->addType('sievePort', 'integer');
		$this->addType('ldapAliasesProvisioning', 'boolean');
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'provisioningDomain' => $this->getProvisioningDomain(),
			'emailTemplate' => $this->getEmailTemplate(),
			'imapUser' => $this->getImapUser(),
			'imapHost' => $this->getImapHost(),
			'imapPort' => $this->getImapPort(),
			'imapSslMode' => $this->getImapSslMode(),
			'smtpUser' => $this->getSmtpUser(),
			'smtpHost' => $this->getSmtpHost(),
			'smtpPort' => $this->getSmtpPort(),
			'smtpSslMode' => $this->getSmtpSslMode(),
			'masterPasswordEnabled' => $this->getMasterPasswordEnabled(),
			'masterPassword' => !empty($this->getMasterPassword()) ? self::MASTER_PASSWORD_PLACEHOLDER : null,
			'masterUser' => $this->getMasterUser(),
			'sieveEnabled' => $this->getSieveEnabled(),
			'sieveUser' => $this->getSieveUser(),
			'sieveHost' => $this->getSieveHost(),
			'sievePort' => $this->getSievePort(),
			'sieveSslMode' => $this->getSieveSslMode(),
			'aliases' => $this->getAliases(),
			'ldapAliasesProvisioning' => $this->getLdapAliasesProvisioning(),
			'ldapAliasesAttribute' => $this->getLdapAliasesAttribute(),
		];
	}

	public function buildImapUser(IUser $user): string {
		if ($this->getImapUser() !== null) {
			$imapUser = $this->buildUserEmail($this->getImapUser(), $user);
		} else {
			$imapUser = $this->buildEmail($user);
		}
		return $this->appendMasterUser($imapUser);
	}

	/**
	 * @param IUser $user
	 * @return string
	 */
	public function buildEmail(IUser $user) {
		return $this->buildUserEmail($this->getEmailTemplate(), $user);
	}

	/**
	 * Replace %USERID% and %EMAIL% to allow special configurations
	 *
	 * @param string $original
	 * @param IUser $user
	 * @return string
	 */
	private function buildUserEmail(string $original, IUser $user) {
		$original = str_replace('%USERID%', $user->getUID(), $original);
		if ($user->getEMailAddress() !== null) {
			$original = str_replace('%EMAIL%', $user->getEMailAddress(), $original);
		}
		return $original;
	}

	public function buildSmtpUser(IUser $user): string {
		if ($this->getSmtpUser() !== null) {
			$smtpUser = $this->buildUserEmail($this->getSmtpUser(), $user);
		} else {
			$smtpUser = $this->buildEmail($user);
		}
		return $this->appendMasterUser($smtpUser);
	}

	public function buildSieveUser(IUser $user): string {
		if ($this->getSieveUser() !== null) {
			$sieveUser = $this->buildUserEmail($this->getSieveUser(), $user);
		} else {
			$sieveUser = $this->buildEmail($user);
		}
		return $this->appendMasterUser($sieveUser);
	}

	/**
	 * The master user contains the separator, e.g. *masteruser
	 */
	private function appendMasterUser(string $loginUser): string {
		if ($this->getMasterPasswordEnabled() !== true || empty($this->getMasterUser())) {
			return $loginUser;
		}
		return $loginUser . $this->getMasterUser();
	}

	/**
	 * The placeholder is sent back by the settings form to keep the stored password
	 */
	public function enableMasterPassword(string $masterPassword, ?string $masterUser): void {
		$this->setMasterPasswordEnabled(true);

		if ($masterPassword !== self::MASTER_PASSWORD_PLACEHOLDER) {
			$this->setMasterPassword($masterPassword);
		}
		$this->setMasterUser($masterUser === '' ? null : $masterUser);
	}

	/**
	 * The fields are marked as updated explicitly because the setters skip a
	 * value identical to the current one, and a freshly built entity is null.
	 */
	public function disableMasterPassword(): void {
		$this->setMasterPasswordEnabled(false);

		$this->masterPassword = null;
		$this->masterUser = null;

		$this->markFieldUpdated('masterPassword');
		$this->markFieldUpdated('masterUser');
	}
}
