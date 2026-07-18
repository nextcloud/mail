<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use ReturnTypeWillChange;

/**
 * An admin-configured OIDC provider used to authenticate individual mail accounts
 * over XOAUTH2. Matched to an account by the user's email domain.
 *
 * @method string getName()
 * @method void setName(string $name)
 * @method string getEmailDomain()
 * @method void setEmailDomain(string $emailDomain)
 * @method string getImapHost()
 * @method void setImapHost(string $imapHost)
 * @method int getImapPort()
 * @method void setImapPort(int $imapPort)
 * @method string getImapSslMode()
 * @method void setImapSslMode(string $imapSslMode)
 * @method string getSmtpHost()
 * @method void setSmtpHost(string $smtpHost)
 * @method int getSmtpPort()
 * @method void setSmtpPort(int $smtpPort)
 * @method string getSmtpSslMode()
 * @method void setSmtpSslMode(string $smtpSslMode)
 * @method string getClientId()
 * @method void setClientId(string $clientId)
 * @method string|null getClientSecret()
 * @method void setClientSecret(?string $clientSecret)
 * @method string getDiscoveryUrl()
 * @method void setDiscoveryUrl(string $discoveryUrl)
 * @method bool getManualEndpoints()
 * @method void setManualEndpoints(bool $manualEndpoints)
 * @method string getAuthorizationEndpoint()
 * @method void setAuthorizationEndpoint(string $authorizationEndpoint)
 * @method string getTokenEndpoint()
 * @method void setTokenEndpoint(string $tokenEndpoint)
 * @method string getScope()
 * @method void setScope(string $scope)
 */
class OidcProvider extends Entity implements JsonSerializable {
	public const CLIENT_SECRET_PLACEHOLDER = '********';

	protected $name;
	protected $emailDomain;
	protected $imapHost;
	protected $imapPort;
	protected $imapSslMode;
	protected $smtpHost;
	protected $smtpPort;
	protected $smtpSslMode;
	protected $clientId;
	protected $clientSecret;
	protected $discoveryUrl;
	protected $manualEndpoints;
	protected $authorizationEndpoint;
	protected $tokenEndpoint;
	protected $scope;

	public function __construct() {
		$this->addType('imapPort', 'integer');
		$this->addType('smtpPort', 'integer');
		$this->addType('manualEndpoints', 'boolean');
	}

	/**
	 * Serialise for the admin UI. The client secret is never sent to the client;
	 * a placeholder signals whether one is set.
	 */
	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'emailDomain' => $this->getEmailDomain(),
			'imapHost' => $this->getImapHost(),
			'imapPort' => $this->getImapPort(),
			'imapSslMode' => $this->getImapSslMode(),
			'smtpHost' => $this->getSmtpHost(),
			'smtpPort' => $this->getSmtpPort(),
			'smtpSslMode' => $this->getSmtpSslMode(),
			'clientId' => $this->getClientId(),
			'clientSecret' => !empty($this->getClientSecret()) ? self::CLIENT_SECRET_PLACEHOLDER : null,
			'discoveryUrl' => $this->getDiscoveryUrl(),
			'manualEndpoints' => $this->getManualEndpoints(),
			'authorizationEndpoint' => $this->getAuthorizationEndpoint(),
			'tokenEndpoint' => $this->getTokenEndpoint(),
			'scope' => $this->getScope(),
		];
	}
}
