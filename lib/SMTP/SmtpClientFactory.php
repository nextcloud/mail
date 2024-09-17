<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\SMTP;

use Horde_Mail_Transport;
use Horde_Mail_Transport_Mail;
use Horde_Mail_Transport_Smtphorde;
use Horde_Smtp_Password_Xoauth2;
use OCA\Mail\Account;
use OCA\Mail\Support\HostNameFactory;
use OCP\IConfig;
use OCP\Security\ICrypto;

class SmtpClientFactory {
	/** @var IConfig */
	private $config;

	/** @var ICrypto */
	private $crypto;

	/** @var HostNameFactory */
	private $hostNameFactory;

	public function __construct(IConfig $config,
		ICrypto $crypto,
		HostNameFactory $hostNameFactory) {
		$this->config = $config;
		$this->crypto = $crypto;
		$this->hostNameFactory = $hostNameFactory;
	}

	/**
	 * @param Account $account
	 *
	 * @return Horde_Mail_Transport
	 */
	public function create(Account $account): Horde_Mail_Transport {
		$mailAccount = $account->getMailAccount();
		$transport = $this->config->getSystemValue('app.mail.transport', 'smtp');
		if ($transport === 'php-mail') {
			return new Horde_Mail_Transport_Mail();
		}

		$decryptedPassword = null;
		if ($mailAccount->getOutboundPassword() !== null) {
			$decryptedPassword = $this->crypto->decrypt($mailAccount->getOutboundPassword());
		}
		$security = $mailAccount->getOutboundSslMode();
		$params = [
			'localhost' => $this->hostNameFactory->getHostName(),
			'host' => $mailAccount->getOutboundHost(),
			'password' => $decryptedPassword,
			'port' => $mailAccount->getOutboundPort(),
			'username' => $mailAccount->getOutboundUser(),
			'secure' => $security === 'none' ? false : $security,
			'timeout' => (int)$this->config->getSystemValue('app.mail.smtp.timeout', 5),
			'context' => [
				'ssl' => [
					'verify_peer' => $this->config->getSystemValueBool('app.mail.verify-tls-peer', true),
					'verify_peer_name' => $this->config->getSystemValueBool('app.mail.verify-tls-peer', true),
				],
			],
		];
		if ($account->getMailAccount()->getAuthMethod() === 'xoauth2') {
			$decryptedAccessToken = $this->crypto->decrypt($account->getMailAccount()->getOauthAccessToken());

			$params['password'] = $decryptedAccessToken; // Not used, but Horde wants this
			$params['xoauth2_token'] = new Horde_Smtp_Password_Xoauth2(
				$account->getMailAccount()->getEmail(),
				$decryptedAccessToken,
			);
		}
		if ($this->config->getSystemValue('debug', false)) {
			$params['debug'] = $this->config->getSystemValue('datadirectory') . '/horde_smtp.log';
		}
		return new Horde_Mail_Transport_Smtphorde($params);
	}
}
