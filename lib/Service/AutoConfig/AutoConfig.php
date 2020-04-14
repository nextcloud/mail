<?php

declare(strict_types=1);

/**
 * @author Bernhard Scheirle <bernhard+git@scheirle.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Service\AutoConfig;

use OCA\Mail\Db\MailAccount;

class AutoConfig {

	/** @var IspDbConfigurationDetector */
	private $ispDbDetector;

	/** @var ConfigurationDetector */
	private $configDetector;

	/**
	 * @param IspDbConfigurationDetector $ispDbDetector
	 * @param ConfigurationDetector $configDetector
	 */
	public function __construct(IspDbConfigurationDetector $ispDbDetector, ConfigurationDetector $configDetector) {
		$this->ispDbDetector = $ispDbDetector;
		$this->configDetector = $configDetector;
	}

	/**
	 * @param string $email
	 * @param string $password
	 * @param string $name
	 * @return null|MailAccount
	 */
	public function createAutoDetected($email, $password, $name) {
		$account = $this->ispDbDetector->detectImapAndSmtp($email, $password, $name);
		if (!is_null($account)) {
			return $account;
		}
		return $this->configDetector->detectImapAndSmtp($email, $password, $name);
	}
}
