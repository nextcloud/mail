<?php

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

namespace OCA\Mail\Tests\Integration\Framework;

use OC;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;

trait ImapTestAccount {

	/**
	 * @return string
	 */
	public function getTestAccountUserId() {
		return 'user12345';
	}

	/**
	 * Create and persist a new mail account
	 *
	 * @return MailAccount
	 */
	public function createTestAccount() {
		/* @var $accountService AccountService */
		$accountService = OC::$server->query(AccountService::class);

		$mailAccount = new MailAccount();
		$mailAccount->setUserId($this->getTestAccountUserId());
		$mailAccount->setEmail('user@domain.tld');
		$mailAccount->setInboundHost('localhost');
		$mailAccount->setInboundPort(993);
		$mailAccount->setInboundSslMode('ssl');
		$mailAccount->setInboundUser('user@domain.tld');
		$mailAccount->setInboundPassword(OC::$server->getCrypto()->encrypt('mypassword'));

		$mailAccount->setOutboundHost('localhost');
		$mailAccount->setOutboundPort(2525);
		$mailAccount->setOutboundUser('user@domain.tld');
		$mailAccount->setOutboundPassword(OC::$server->getCrypto()->encrypt('mypassword'));
		$mailAccount->setOutboundSslMode('none');
		return $accountService->save($mailAccount);
	}

}
