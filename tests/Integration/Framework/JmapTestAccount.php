<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Framework;

use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;
use OCP\Security\ICrypto;
use OCP\Server;

trait JmapTestAccount {
	public function getTestAccountUserId(): string {
		return 'user12345';
	}

	/**
	 * Create and persist a JMAP mail account pointing at the test server.
	 */
	public function createTestAccount(?string $userId = null): MailAccount {
		/* @var $accountService AccountService */
		$accountService = Server::get(AccountService::class);

		$mailAccount = new MailAccount();
		$mailAccount->setUserId($userId ?? $this->getTestAccountUserId());
		$mailAccount->setName('Tester');
		$mailAccount->setEmail('user@example.com');
		$mailAccount->setProtocol(MailAccount::PROTOCOL_JMAP);
		$mailAccount->setInboundHost('127.0.0.1');
		$mailAccount->setInboundPort(10080);
		$mailAccount->setInboundSslMode('none');
		$mailAccount->setInboundUser('user@example.com');
		$mailAccount->setInboundPassword(Server::get(ICrypto::class)->encrypt('mypassword'));

		$saved = $accountService->save($mailAccount);

		return $saved;
	}
}
