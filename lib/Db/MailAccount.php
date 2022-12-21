<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Class MailAccount
 *
 * @package OCA\Mail\Db
 *
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getEmail()
 * @method void setEmail(string $email)
 * @method string getInboundHost()
 * @method void setInboundHost(string $inboundHost)
 * @method integer getInboundPort()
 * @method void setInboundPort(integer $inboundPort)
 * @method string getInboundSslMode()
 * @method void setInboundSslMode(string $inboundSslMode)
 * @method string getInboundUser()
 * @method void setInboundUser(string $inboundUser)
 * @method string|null getInboundPassword()
 * @method void setInboundPassword(?string $inboundPassword)
 * @method string getOutboundHost()
 * @method void setOutboundHost(string $outboundHost)
 * @method integer getOutboundPort()
 * @method void setOutboundPort(integer $outboundPort)
 * @method string getOutboundSslMode()
 * @method void setOutboundSslMode(string $outboundSslMode)
 * @method string getOutboundUser()
 * @method void setOutboundUser(string $outboundUser)
 * @method string|null getOutboundPassword()
 * @method void setOutboundPassword(?string $outboundPassword)
 * @method string|null getSignature()
 * @method void setSignature(string|null $signature)
 * @method int getLastMailboxSync()
 * @method void setLastMailboxSync(int $time)
 * @method string getEditorMode()
 * @method void setEditorMode(string $editorMode)
 * @method int|null getProvisioningId()
 * @method void setProvisioningId(int $provisioningId)
 * @method int getOrder()
 * @method void setOrder(int $order)
 * @method bool|null getShowSubscribedOnly()
 * @method void setShowSubscribedOnly(bool $showSubscribedOnly)
 * @method string|null getPersonalNamespace()
 * @method void setPersonalNamespace(string|null $personalNamespace)
 * @method void setDraftsMailboxId(?int $id)
 * @method int|null getDraftsMailboxId()
 * @method void setSentMailboxId(?int $id)
 * @method int|null getSentMailboxId()
 * @method void setTrashMailboxId(?int $id)
 * @method int|null getTrashMailboxId()
 * @method void setArchiveMailboxId(?int $id)
 * @method int|null getArchiveMailboxId()
 * @method bool|null isSieveEnabled()
 * @method void setSieveEnabled(bool $sieveEnabled)
 * @method string|null getSieveHost()
 * @method void setSieveHost(?string $sieveHost)
 * @method int|null getSievePort()
 * @method void setSievePort(?int $sievePort)
 * @method string|null getSieveSslMode()
 * @method void setSieveSslMode(?string $sieveSslMode)
 * @method string|null getSieveUser()
 * @method void setSieveUser(?string $sieveUser)
 * @method string|null getSievePassword()
 * @method void setSievePassword(?string $sievePassword)
 * @method bool|null isSignatureAboveQuote()
 * @method void setSignatureAboveQuote(bool $signatureAboveQuote)
 * @method string getAuthMethod()
 * @method void setAuthMethod(string $method)
 * @method int getSignatureMode()
 * @method void setSignatureMode(int $signatureMode)
 * @method string getOauthAccessToken()
 * @method void setOauthAccessToken(string $token)
 * @method string getOauthRefreshToken()
 * @method void setOauthRefreshToken(string $token)
 * @method int|null getOauthTokenTtl()
 * @method void setOauthTokenTtl(int $ttl)
 */
class MailAccount extends Entity {
	public const SIGNATURE_MODE_PLAIN = 0;
	public const SIGNATURE_MODE_HTML = 1;

	protected $userId;
	protected $name;
	protected $email;
	protected $inboundHost;
	protected $inboundPort;
	protected $inboundSslMode;
	protected $inboundUser;
	protected $inboundPassword;
	protected $outboundHost;
	protected $outboundPort;
	protected $outboundSslMode;
	protected $outboundUser;
	protected $outboundPassword;
	protected $signature;
	protected $lastMailboxSync;
	protected $editorMode;
	protected $order;
	protected $showSubscribedOnly;
	protected $personalNamespace;
	protected $authMethod;
	protected $oauthAccessToken;
	protected $oauthRefreshToken;
	protected $oauthTokenTtl;

	/** @var int|null */
	protected $draftsMailboxId;

	/** @var int|null */
	protected $sentMailboxId;

	/** @var int|null */
	protected $trashMailboxId;

	/** @var int|null */
	protected $archiveMailboxId;

	/** @var bool */
	protected $sieveEnabled = false;
	/** @var string|null */
	protected $sieveHost;
	/** @var integer|null */
	protected $sievePort;
	/** @var string|null */
	protected $sieveSslMode;
	/** @var string|null */
	protected $sieveUser;
	/** @var string|null */
	protected $sievePassword;
	/** @var bool */
	protected $signatureAboveQuote = false;

	/** @var int|null */
	protected $provisioningId;

	/** @var int */
	protected $signatureMode;

	/**
	 * @param array $params
	 */
	public function __construct(array $params = []) {
		if (isset($params['accountId'])) {
			$this->setId($params['accountId']);
		}
		if (isset($params['accountName'])) {
			$this->setName($params['accountName']);
		}
		if (isset($params['emailAddress'])) {
			$this->setEmail($params['emailAddress']);
		}

		if (isset($params['imapHost'])) {
			$this->setInboundHost($params['imapHost']);
		}
		if (isset($params['imapPort'])) {
			$this->setInboundPort($params['imapPort']);
		}
		if (isset($params['imapSslMode'])) {
			$this->setInboundSslMode($params['imapSslMode']);
		}
		if (isset($params['imapUser'])) {
			$this->setInboundUser($params['imapUser']);
		}
		if (isset($params['imapPassword'])) {
			$this->setInboundPassword($params['imapPassword']);
		}

		if (isset($params['smtpHost'])) {
			$this->setOutboundHost($params['smtpHost']);
		}
		if (isset($params['smtpPort'])) {
			$this->setOutboundPort($params['smtpPort']);
		}
		if (isset($params['smtpSslMode'])) {
			$this->setOutboundSslMode($params['smtpSslMode']);
		}
		if (isset($params['smtpUser'])) {
			$this->setOutboundUser($params['smtpUser']);
		}
		if (isset($params['smtpPassword'])) {
			$this->setOutboundPassword($params['smtpPassword']);
		}
		if (isset($params['showSubscribedOnly'])) {
			$this->setShowSubscribedOnly($params['showSubscribedOnly']);
		}
		if (isset($params['signatureAboveQuote'])) {
			$this->setSignatureAboveQuote($params['signatureAboveQuote']);
		}

		$this->addType('inboundPort', 'integer');
		$this->addType('outboundPort', 'integer');
		$this->addType('lastMailboxSync', 'integer');
		$this->addType('provisioningId', 'integer');
		$this->addType('order', 'integer');
		$this->addType('showSubscribedOnly', 'boolean');
		$this->addType('personalNamespace', 'string');
		$this->addType('draftsMailboxId', 'integer');
		$this->addType('sentMailboxId', 'integer');
		$this->addType('trashMailboxId', 'integer');
		$this->addType('archiveMailboxId', 'integer');
		$this->addType('sieveEnabled', 'boolean');
		$this->addType('sievePort', 'integer');
		$this->addType('signatureAboveQuote', 'boolean');
		$this->addType('signatureMode', 'int');
	}

	/**
	 * @return array
	 */
	public function toJson() {
		$result = [
			'id' => $this->getId(),
			'accountId' => $this->getId(),
			'name' => $this->getName(),
			'order' => $this->getOrder(),
			'emailAddress' => $this->getEmail(),
			'imapHost' => $this->getInboundHost(),
			'imapPort' => $this->getInboundPort(),
			'imapUser' => $this->getInboundUser(),
			'imapSslMode' => $this->getInboundSslMode(),
			'signature' => $this->getSignature(),
			'editorMode' => $this->getEditorMode(),
			'provisioningId' => $this->getProvisioningId(),
			'showSubscribedOnly' => ($this->getShowSubscribedOnly() === true),
			'personalNamespace' => $this->getPersonalNamespace(),
			'draftsMailboxId' => $this->getDraftsMailboxId(),
			'sentMailboxId' => $this->getSentMailboxId(),
			'trashMailboxId' => $this->getTrashMailboxId(),
			'archiveMailboxId' => $this->getArchiveMailboxId(),
			'sieveEnabled' => ($this->isSieveEnabled() === true),
			'signatureAboveQuote' => ($this->isSignatureAboveQuote() === true),
			'signatureMode' => $this->getSignatureMode(),
		];

		if (!is_null($this->getOutboundHost())) {
			$result['smtpHost'] = $this->getOutboundHost();
			$result['smtpPort'] = $this->getOutboundPort();
			$result['smtpUser'] = $this->getOutboundUser();
			$result['smtpSslMode'] = $this->getOutboundSslMode();
		}

		if ($this->isSieveEnabled()) {
			$result['sieveHost'] = $this->getSieveHost();
			$result['sievePort'] = $this->getSievePort();
			$result['sieveUser'] = $this->getSieveUser();
			$result['sieveSslMode'] = $this->getSieveSslMode();
		}

		return $result;
	}
}
