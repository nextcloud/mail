<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
 * @method void setProvisioningId(int|null $provisioningId)
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
 * @method void setSnoozeMailboxId(?int $id)
 * @method int|null getSnoozeMailboxId()
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
 * @method int|null getSmimeCertificateId()
 * @method void setSmimeCertificateId(int|null $smimeCertificateId)
 * @method int|null getQuotaPercentage()
 * @method void setQuotaPercentage(int $quota);
 * @method int|null getTrashRetentionDays()
 * @method void setTrashRetentionDays(int|null $trashRetentionDays)
 * @method int|null getJunkMailboxId()
 * @method void setJunkMailboxId(?int $id)
 * @method bool getSearchBody()
 * @method void setSearchBody(bool $searchBody)
 * @method bool|null getOooFollowsSystem()
 * @method void setOooFollowsSystem(bool $oooFollowsSystem)
 * @method bool getDebug()
 * @method void setDebug(bool $debug)
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

	/** @var int|null */
	protected $snoozeMailboxId;

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

	/** @var int|null */
	protected $smimeCertificateId;

	/** @var int|null */
	protected $quotaPercentage;

	/** @var int|null */
	protected $trashRetentionDays;

	protected ?int $junkMailboxId = null;

	/** @var bool */
	protected $searchBody = false;

	/** @var bool|null */
	protected $oooFollowsSystem;

	protected bool $debug = false;

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
		if (isset($params['trashRetentionDays'])) {
			$this->setTrashRetentionDays($params['trashRetentionDays']);
		}
		if (isset($params['outOfOfficeFollowsSystem'])) {
			$this->setOutOfOfficeFollowsSystem($params['outOfOfficeFollowsSystem']);
		}
		if (isset($params['debug'])) {
			$this->setDebug($params['debug']);
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
		$this->addType('snoozeMailboxId', 'integer');
		$this->addType('sieveEnabled', 'boolean');
		$this->addType('sievePort', 'integer');
		$this->addType('signatureAboveQuote', 'boolean');
		$this->addType('signatureMode', 'integer');
		$this->addType('smimeCertificateId', 'integer');
		$this->addType('quotaPercentage', 'integer');
		$this->addType('trashRetentionDays', 'integer');
		$this->addType('junkMailboxId', 'integer');
		$this->addType('searchBody', 'boolean');
		$this->addType('oooFollowsSystem', 'boolean');
		$this->addType('debug', 'boolean');
	}

	public function getOutOfOfficeFollowsSystem(): bool {
		return $this->getOooFollowsSystem() === true;
	}

	public function setOutOfOfficeFollowsSystem(bool $outOfOfficeFollowsSystem): void {
		$this->setOooFollowsSystem($outOfOfficeFollowsSystem);
	}

	public function canAuthenticateImap(): bool {
		return isset($this->inboundPassword) || isset($this->oauthAccessToken);
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
			'snoozeMailboxId' => $this->getSnoozeMailboxId(),
			'sieveEnabled' => ($this->isSieveEnabled() === true),
			'signatureAboveQuote' => ($this->isSignatureAboveQuote() === true),
			'signatureMode' => $this->getSignatureMode(),
			'smimeCertificateId' => $this->getSmimeCertificateId(),
			'quotaPercentage' => $this->getQuotaPercentage(),
			'trashRetentionDays' => $this->getTrashRetentionDays(),
			'junkMailboxId' => $this->getJunkMailboxId(),
			'searchBody' => $this->getSearchBody(),
			'outOfOfficeFollowsSystem' => $this->getOutOfOfficeFollowsSystem(),
			'debug' => $this->getDebug(),
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
