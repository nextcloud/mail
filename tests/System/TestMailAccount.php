<?php

declare(strict_types=1);

/**
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

namespace OCA\Mail\Tests\System;

use OC;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;

class TestMailAccount {

	/**
	 * @var string
	 */
	private $username;

	public function __construct(string $username) {
		$this->username = $username;
	}

	public function create(string $uid, AccountService $accountService): MailAccount {
		exec("docker exec -it ncimaptest /opt/bin/useradd $this->username mypasswd");

		$mailAccount = new MailAccount();
		$mailAccount->setUserId($uid);
		$mailAccount->setEmail($this->username);
		$mailAccount->setInboundHost('localhost');
		$mailAccount->setInboundPort(993);
		$mailAccount->setInboundSslMode('ssl');
		$mailAccount->setInboundUser($this->username);
		$mailAccount->setInboundPassword(OC::$server->getCrypto()->encrypt('mypasswd'));

		$mailAccount->setOutboundHost('localhost');
		$mailAccount->setOutboundPort(2525);
		$mailAccount->setOutboundUser($this->username);
		$mailAccount->setOutboundPassword(OC::$server->getCrypto()->encrypt('mypasswd'));
		$mailAccount->setOutboundSslMode('none');

		$account = $accountService->save($mailAccount);

		return $account;
	}

}