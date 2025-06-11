<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 owncloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Command;

use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;
use OCP\IUserManager;
use OCP\Security\ICrypto;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CreateAccount extends Command {
	public const ARGUMENT_USER_ID = 'user-id';
	public const ARGUMENT_NAME = 'name';
	public const ARGUMENT_EMAIL = 'email';
	public const ARGUMENT_AUTH_METHOD = 'auth-method';
	public const ARGUMENT_IMAP_HOST = 'imap-host';
	public const ARGUMENT_IMAP_PORT = 'imap-port';
	public const ARGUMENT_IMAP_SSL_MODE = 'imap-ssl-mode';
	public const ARGUMENT_IMAP_USER = 'imap-user';
	public const ARGUMENT_IMAP_PASSWORD = 'imap-password';
	public const ARGUMENT_SMTP_HOST = 'smtp-host';
	public const ARGUMENT_SMTP_PORT = 'smtp-port';
	public const ARGUMENT_SMTP_SSL_MODE = 'smtp-ssl-mode';
	public const ARGUMENT_SMTP_USER = 'smtp-user';
	public const ARGUMENT_SMTP_PASSWORD = 'smtp-password';

	private AccountService $accountService;
	private ICrypto $crypto;
	private IUserManager $userManager;

	public function __construct(AccountService $service,
		ICrypto $crypto,
		IUserManager $userManager) {
		parent::__construct();

		$this->accountService = $service;
		$this->crypto = $crypto;
		$this->userManager = $userManager;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('mail:account:create');
		$this->setDescription('creates IMAP account');
		$this->addArgument(self::ARGUMENT_USER_ID, InputArgument::REQUIRED);
		$this->addArgument(self::ARGUMENT_NAME, InputArgument::REQUIRED);
		$this->addArgument(self::ARGUMENT_EMAIL, InputArgument::REQUIRED);

		$this->addArgument(self::ARGUMENT_IMAP_HOST, InputArgument::REQUIRED);
		$this->addArgument(self::ARGUMENT_IMAP_PORT, InputArgument::REQUIRED);
		$this->addArgument(self::ARGUMENT_IMAP_SSL_MODE, InputArgument::REQUIRED);
		$this->addArgument(self::ARGUMENT_IMAP_USER, InputArgument::REQUIRED);
		$this->addArgument(self::ARGUMENT_IMAP_PASSWORD, InputArgument::REQUIRED);

		$this->addArgument(self::ARGUMENT_SMTP_HOST, InputArgument::REQUIRED);
		$this->addArgument(self::ARGUMENT_SMTP_PORT, InputArgument::REQUIRED);
		$this->addArgument(self::ARGUMENT_SMTP_SSL_MODE, InputArgument::REQUIRED);
		$this->addArgument(self::ARGUMENT_SMTP_USER, InputArgument::REQUIRED);
		$this->addArgument(self::ARGUMENT_SMTP_PASSWORD, InputArgument::REQUIRED);

		$this->addArgument(self::ARGUMENT_AUTH_METHOD, InputArgument::OPTIONAL, 'password or xoauth2', 'password');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument(self::ARGUMENT_USER_ID);
		$name = $input->getArgument(self::ARGUMENT_NAME);
		$email = $input->getArgument(self::ARGUMENT_EMAIL);
		$authMethod = $input->getArgument(self::ARGUMENT_AUTH_METHOD);

		$imapHost = $input->getArgument(self::ARGUMENT_IMAP_HOST);
		$imapPort = $input->getArgument(self::ARGUMENT_IMAP_PORT);
		$imapSslMode = $input->getArgument(self::ARGUMENT_IMAP_SSL_MODE);
		$imapUser = $input->getArgument(self::ARGUMENT_IMAP_USER);
		$imapPassword = $input->getArgument(self::ARGUMENT_IMAP_PASSWORD);

		$smtpHost = $input->getArgument(self::ARGUMENT_SMTP_HOST);
		$smtpPort = $input->getArgument(self::ARGUMENT_SMTP_PORT);
		$smtpSslMode = $input->getArgument(self::ARGUMENT_SMTP_SSL_MODE);
		$smtpUser = $input->getArgument(self::ARGUMENT_SMTP_USER);
		$smtpPassword = $input->getArgument(self::ARGUMENT_SMTP_PASSWORD);

		if (!$this->userManager->userExists($userId)) {
			$output->writeln("<error>User $userId does not exist</error>");
			return 1;
		}

		$account = new MailAccount();
		$account->setUserId($userId);
		$account->setName($name);
		$account->setEmail($email);
		$account->setAuthMethod($authMethod);

		$account->setInboundHost($imapHost);
		$account->setInboundPort((int)$imapPort);
		$account->setInboundSslMode($imapSslMode);
		$account->setInboundUser($imapUser);
		$account->setInboundPassword($this->crypto->encrypt($imapPassword));

		$account->setOutboundHost($smtpHost);
		$account->setOutboundPort((int)$smtpPort);
		$account->setOutboundSslMode($smtpSslMode);
		$account->setOutboundUser($smtpUser);
		$account->setOutboundPassword($this->crypto->encrypt($smtpPassword));

		$account = $this->accountService->save($account);

		$output->writeln('<info>Account ' . $account->getId() . " for $email created</info>");

		return 0;
	}
}
