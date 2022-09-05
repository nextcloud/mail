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
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\Service\AccountService;
use Psr\Log\NullLogger;

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
	public function createTestAccount(string $userId = null) {
		/* @var $accountService AccountService */
		$accountService = OC::$server->query(AccountService::class);

		$mailAccount = new MailAccount();
		$mailAccount->setUserId($userId ?? $this->getTestAccountUserId());
		$mailAccount->setName('Tester');
		$mailAccount->setEmail('user@domain.tld');
		$mailAccount->setInboundHost('127.0.0.1');
		$mailAccount->setInboundPort(993);
		$mailAccount->setInboundSslMode('ssl');
		$mailAccount->setInboundUser('user@domain.tld');
		$mailAccount->setInboundPassword(OC::$server->getCrypto()->encrypt('mypassword'));

		$mailAccount->setOutboundHost('127.0.0.1');
		$mailAccount->setOutboundPort(25);
		$mailAccount->setOutboundUser('user@domain.tld');
		$mailAccount->setOutboundPassword(OC::$server->getCrypto()->encrypt('mypassword'));
		$mailAccount->setOutboundSslMode('none');
		$acc = $accountService->save($mailAccount);

		/** @var MailboxSync $mbSync */
		$mbSync = OC::$server->query(MailboxSync::class);
		$mbSync->sync(new Account($mailAccount), new NullLogger());

		return $acc;
	}
}
