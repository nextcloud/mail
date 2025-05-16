<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Framework;

use OC;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\Service\AccountService;
use OCP\Server;
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
	public function createTestAccount(?string $userId = null) {
		/* @var $accountService AccountService */
		$accountService = Server::get(AccountService::class);

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
		$mailAccount->setDebug(false);
		$acc = $accountService->save($mailAccount);

		/** @var MailboxSync $mbSync */
		$mbSync = Server::get(MailboxSync::class);
		$mbSync->sync(new Account($mailAccount), new NullLogger());

		return $acc;
	}
}
