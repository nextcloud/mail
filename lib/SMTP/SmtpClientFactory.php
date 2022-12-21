<?php

declare(strict_types=1);

/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 *
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
				$account->getEmail(),
				$decryptedAccessToken,
			);
		}
		if ($this->config->getSystemValue('debug', false)) {
			$params['debug'] = $this->config->getSystemValue('datadirectory') . '/horde_smtp.log';
		}
		return new Horde_Mail_Transport_Smtphorde($params);
	}
}
