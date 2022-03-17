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
		$this->addType('sieveEnabled', 'boolean');
		$this->addType('sievePort', 'integer');
		$this->addType('ldapAliasesProvisioning', 'boolean');
	}

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

	/**
	 * @return string
	 */
	public function buildImapUser(IUser $user) {
		if (!is_null($this->getImapUser())) {
			return $this->buildUserEmail($this->getImapUser(), $user);
		}
		return $this->buildEmail($user);
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
		if ($user->getUID() !== null) {
			$original = str_replace('%USERID%', $user->getUID(), $original);
		}
		if ($user->getEMailAddress() !== null) {
			$original = str_replace('%EMAIL%', $user->getEMailAddress(), $original);
		}
		return $original;
	}

	/**
	 * @param IUser $user
	 * @return string
	 */
	public function buildSmtpUser(IUser $user) {
		if (!is_null($this->getSmtpUser())) {
			return $this->buildUserEmail($this->getSmtpUser(), $user);
		}
		return $this->buildEmail($user);
	}

	/**
	 * @param IUser $user
	 * @return string
	 */
	public function buildSieveUser(IUser $user) {
		if (!is_null($this->getSieveUser())) {
			return $this->buildUserEmail($this->getSieveUser(), $user);
		}
		return $this->buildEmail($user);
	}
}
