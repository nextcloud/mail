<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015-2016 owncloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Mail;

use Horde_Imap_Client_Socket;
use JsonSerializable;
use OC;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\Quota;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\Security\ICrypto;
use ReturnTypeWillChange;

class Account implements JsonSerializable {
	/** @var MailAccount */
	private $account;

	/** @var Horde_Imap_Client_Socket */
	private $client;

	/** @var ICrypto */
	private $crypto;

	/** @var IConfig */
	private $config;

	/** @var ICacheFactory */
	private $memcacheFactory;

	/** @var Alias */
	private $alias;

	/**
	 * @param MailAccount $account
	 */
	public function __construct(MailAccount $account) {
		$this->account = $account;
		$this->crypto = OC::$server->getCrypto();
		$this->config = OC::$server->getConfig();
		$this->memcacheFactory = OC::$server->getMemcacheFactory();
	}

	public function __destruct() {
		if ($this->client !== null) {
			$this->client->logout();
		}
	}

	public function getMailAccount(): MailAccount {
		return $this->account;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->account->getId();
	}

	/**
	 * @param Alias|null $alias
	 * @return void
	 */
	public function setAlias($alias) {
		$this->alias = $alias;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->alias ? $this->alias->getName() : $this->account->getName();
	}

	/**
	 * @return string
	 */
	public function getEMailAddress() {
		return $this->account->getEmail();
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return $this->account->toJson();
	}

	/**
	 * Convert special security mode values into Horde parameters
	 *
	 * @param string $sslMode
	 * @return false|string
	 */
	protected function convertSslMode($sslMode) {
		switch ($sslMode) {
			case 'none':
				return false;
		}
		return $sslMode;
	}

	public function getEmail(): string {
		return $this->account->getEmail();
	}

	/**
	 * @return string
	 */
	public function getUserId() {
		return $this->account->getUserId();
	}

	/**
	 * Set the quota percentage
	 * @param Quota $quota
	 * @return void
	 */
	public function calculateAndSetQuotaPercentage(Quota $quota): void {
		if ($quota->getLimit() === 0) {
			$this->account->setQuotaPercentage(0);
			return;
		}
		$percentage = (int)round($quota->getUsage() / $quota->getLimit() * 100);
		$this->account->setQuotaPercentage($percentage);
	}

	public function getQuotaPercentage(): ?int {
		return $this->account->getQuotaPercentage();
	}
}
