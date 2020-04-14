<?php

declare(strict_types=1);

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Service\AutoConfig;

use OCA\Mail\Db\MailAccount;

class ConfigurationDetector {

	/** @var ImapServerDetector */
	private $imapServerDetector;

	/** @var SmtpServerDetector */
	private $smtpServerDetector;

	/**
	 * @param ImapServerDetector $imapServerDetector
	 * @param SmtpServerDetector $smtpServerDetector
	 */
	public function __construct(ImapServerDetector $imapServerDetector, SmtpServerDetector $smtpServerDetector) {
		$this->imapServerDetector = $imapServerDetector;
		$this->smtpServerDetector = $smtpServerDetector;
	}

	/**
	 * @param string $email
	 * @param string $password
	 * @param string $name
	 * @return null|MailAccount
	 */
	public function detectImapAndSmtp(string $email, string $password, string $name) {
		$account = $this->imapServerDetector->detect($email, $password, $name);
		if (is_null($account)) {
			return null;
		}

		$this->smtpServerDetector->detect($account, $email, $password);

		return $account;
	}
}
