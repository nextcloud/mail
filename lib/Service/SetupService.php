<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use Horde_Imap_Client_Exception;
use Horde_Mail_Exception;
use Horde_Mail_Transport_Smtphorde;
use InvalidArgumentException;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\CouldNotConnectException;
use OCA\Mail\Exception\ServiceException;
use OCP\Security\ICrypto;
use function in_array;

class SetupService {
	/** @var ICrypto */
	private $crypto;

	public function __construct(
		private readonly \OCA\Mail\Service\AccountService $accountService,
		ICrypto $crypto,
		private readonly \OCA\Mail\SMTP\SmtpClientFactory $smtpClientFactory,
		private readonly \OCA\Mail\IMAP\IMAPClientFactory $imapClientFactory,
		private readonly \Psr\Log\LoggerInterface $logger,
		private readonly \OCA\Mail\Db\TagMapper $tagMapper
	) {
		$this->crypto = $crypto;
	}

	/**
	 * @throws CouldNotConnectException
	 * @throws ServiceException
	 */
	public function createNewAccount(string $accountName,
		string $emailAddress,
		string $imapHost,
		int $imapPort,
		string $imapSslMode,
		string $imapUser,
		?string $imapPassword,
		string $smtpHost,
		int $smtpPort,
		string $smtpSslMode,
		string $smtpUser,
		?string $smtpPassword,
		string $uid,
		string $authMethod,
		?int $accountId = null): Account {
		$this->logger->info('Setting up manually configured account');
		$newAccount = new MailAccount([
			'accountId' => $accountId,
			'accountName' => $accountName,
			'emailAddress' => $emailAddress,
			'imapHost' => $imapHost,
			'imapPort' => $imapPort,
			'imapSslMode' => $imapSslMode,
			'imapUser' => $imapUser,
			'smtpHost' => $smtpHost,
			'smtpPort' => $smtpPort,
			'smtpSslMode' => $smtpSslMode,
			'smtpUser' => $smtpUser,
		]);
		$newAccount->setUserId($uid);
		if ($authMethod === 'password' && $imapPassword !== null) {
			$newAccount->setInboundPassword($this->crypto->encrypt($imapPassword));
		}
		if ($authMethod === 'password' && $smtpPassword !== null) {
			$newAccount->setOutboundPassword($this->crypto->encrypt($smtpPassword));
		}
		if (!in_array($authMethod, ['password', 'xoauth2'], true)) {
			throw new InvalidArgumentException('Invalid auth method ' . $authMethod);
		}
		$newAccount->setAuthMethod($authMethod);

		$account = new Account($newAccount);
		if ($authMethod === 'password' && $imapPassword !== null) {
			$this->logger->debug('Connecting to account {account}', ['account' => $newAccount->getEmail()]);
			$this->testConnectivity($account);
		}

		$this->accountService->save($newAccount);
		$this->logger->debug('account created ' . $newAccount->getId());

		$this->tagMapper->createDefaultTags($newAccount);

		return $account;
	}

	/**
	 * @throws CouldNotConnectException
	 */
	protected function testConnectivity(Account $account): void {
		$mailAccount = $account->getMailAccount();

		$imapClient = $this->imapClientFactory->getClient($account);
		try {
			$imapClient->login();
		} catch (Horde_Imap_Client_Exception $e) {
			throw new CouldNotConnectException($e, 'IMAP', $mailAccount->getInboundHost(), $mailAccount->getInboundPort());
		} finally {
			$imapClient->logout();
		}

		$transport = $this->smtpClientFactory->create($account);
		if ($transport instanceof Horde_Mail_Transport_Smtphorde) {
			try {
				$transport->getSMTPObject();
			} catch (Horde_Mail_Exception $e) {
				throw new CouldNotConnectException($e, 'SMTP', $mailAccount->getOutboundHost(), $mailAccount->getOutboundPort());
			}
		}
	}
}
