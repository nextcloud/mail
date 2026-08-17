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
 * @method void setMasterPassword(string $masterPassword)
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

	/**
	 * Captured names reach the directory as the requested attribute of an LDAP read,
	 * so the class stays too narrow for any LDAP special character to pass.
	 */
	private const LDAP_PLACEHOLDER_PATTERN = '%LDAP:([A-Za-z][A-Za-z0-9-]*)%';
	private const LDAP_PLACEHOLDER_REGEX = '/' . self::LDAP_PLACEHOLDER_PATTERN . '/';
	private const LDAP_PLACEHOLDER_ANCHORED_REGEX = '/^' . self::LDAP_PLACEHOLDER_PATTERN . '$/D';

	/** Anything shaped like an LDAP placeholder, including unsupported syntax */
	private const LDAP_PLACEHOLDER_LOOSE_REGEX = '/%ldap:[^%]*%/i';

	/**
	 * '%' opens and closes a placeholder, so replacing the token types one after
	 * another lets adjacent placeholders consume each other's delimiter. All of
	 * them therefore have to be substituted in a single pass.
	 */
	private const PLACEHOLDER_REGEX = '/%USERID%|%EMAIL%|' . self::LDAP_PLACEHOLDER_PATTERN . '/';

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
	 * @param IUser $user
	 * @param array<string, string> $ldapValues resolved %LDAP:attr% tokens, keyed by full token
	 * @return string
	 */
	public function buildImapUser(IUser $user, array $ldapValues = []) {
		if (!is_null($this->getImapUser())) {
			return $this->buildUserEmail($this->getImapUser(), $user, $ldapValues);
		}
		return $this->buildEmail($user, $ldapValues);
	}

	/**
	 * @param IUser $user
	 * @param array<string, string> $ldapValues resolved %LDAP:attr% tokens, keyed by full token
	 * @return string
	 */
	public function buildEmail(IUser $user, array $ldapValues = []) {
		return $this->buildUserEmail($this->getEmailTemplate(), $user, $ldapValues);
	}

	/**
	 * Unique attribute names referenced via %LDAP:attr%, spelled as in the templates.
	 * The sieve template only counts while sieve is enabled, as its account settings
	 * are not built otherwise.
	 *
	 * @return string[]
	 */
	public function ldapAttributesInTemplates(): array {
		$attributes = [];
		$templates = [
			$this->getEmailTemplate(),
			$this->getImapUser(),
			$this->getSmtpUser(),
		];
		if ($this->getSieveEnabled()) {
			$templates[] = $this->getSieveUser();
		}
		foreach ($templates as $template) {
			if ($template === null) {
				continue;
			}
			if (preg_match_all(self::LDAP_PLACEHOLDER_REGEX, $template, $matches) > 0) {
				$attributes = array_merge($attributes, $matches[1]);
			}
		}
		return array_values(array_unique($attributes));
	}

	/**
	 * Placeholders using unsupported syntax, e.g. a lowercase prefix or an attribute
	 * option. They would never be substituted and end up literally in an account.
	 *
	 * @return string[]
	 */
	public static function findMalformedLdapPlaceholders(?string $template): array {
		if ($template === null || preg_match_all(self::LDAP_PLACEHOLDER_LOOSE_REGEX, $template, $matches) === 0) {
			return [];
		}
		$malformed = [];
		foreach ($matches[0] as $candidate) {
			if (preg_match(self::LDAP_PLACEHOLDER_ANCHORED_REGEX, $candidate) !== 1) {
				$malformed[] = $candidate;
			}
		}
		return array_values(array_unique($malformed));
	}

	/**
	 * Replace %USERID%, %EMAIL% and %LDAP:attr% to allow special configurations.
	 * Tokens without a value stay literal.
	 *
	 * @param string $original
	 * @param IUser $user
	 * @param array<string, string> $ldapValues resolved %LDAP:attr% tokens, keyed by full token
	 * @return string
	 */
	private function buildUserEmail(string $original, IUser $user, array $ldapValues = []) {
		$replaced = preg_replace_callback(
			self::PLACEHOLDER_REGEX,
			static function (array $match) use ($user, $ldapValues): string {
				switch ($match[0]) {
					case '%USERID%':
						return $user->getUID();
					case '%EMAIL%':
						return $user->getEMailAddress() ?? $match[0];
					default:
						return $ldapValues[$match[0]] ?? $match[0];
				}
			},
			$original
		);
		return $replaced ?? $original;
	}

	/**
	 * @param IUser $user
	 * @param array<string, string> $ldapValues resolved %LDAP:attr% tokens, keyed by full token
	 * @return string
	 */
	public function buildSmtpUser(IUser $user, array $ldapValues = []) {
		if (!is_null($this->getSmtpUser())) {
			return $this->buildUserEmail($this->getSmtpUser(), $user, $ldapValues);
		}
		return $this->buildEmail($user, $ldapValues);
	}

	/**
	 * @param IUser $user
	 * @param array<string, string> $ldapValues resolved %LDAP:attr% tokens, keyed by full token
	 * @return string
	 */
	public function buildSieveUser(IUser $user, array $ldapValues = []) {
		if (!is_null($this->getSieveUser())) {
			return $this->buildUserEmail($this->getSieveUser(), $user, $ldapValues);
		}
		return $this->buildEmail($user, $ldapValues);
	}
}
