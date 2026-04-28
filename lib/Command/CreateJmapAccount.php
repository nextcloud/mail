<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Classification\ClassificationSettingsService;
use OCP\IUserManager;
use OCP\Security\ICrypto;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CreateJmapAccount extends Command {
	public const ARGUMENT_USER_ID = 'user-id';
	public const ARGUMENT_NAME = 'name';
	public const ARGUMENT_EMAIL = 'email';
	public const ARGUMENT_HOST = 'host';
	public const ARGUMENT_PORT = 'port';
	public const ARGUMENT_SSL_MODE = 'ssl-mode';
	public const ARGUMENT_BAUTH_USER = 'basic-auth-user';
	public const ARGUMENT_BAUTH_PASSWORD = 'basic-auth-password';
	public const ARGUMENT_PATH = 'path';

	public function __construct(
		private AccountService $accountService,
		private ICrypto $crypto,
		private IUserManager $userManager,
		private ClassificationSettingsService $classificationSettingsService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('mail:account:create-jmap');
		$this->setDescription('creates a JMAP mail account');
		$this->addArgument(self::ARGUMENT_USER_ID, InputArgument::REQUIRED, 'user to add the account for');
		$this->addArgument(self::ARGUMENT_NAME, InputArgument::REQUIRED, 'display name of the account');
		$this->addArgument(self::ARGUMENT_EMAIL, InputArgument::REQUIRED, 'email address');
		$this->addArgument(self::ARGUMENT_HOST, InputArgument::REQUIRED, 'JMAP server hostname (e.g. mail.example.com)');
		$this->addArgument(self::ARGUMENT_PORT, InputArgument::REQUIRED, 'JMAP server port (e.g. 443)');
		$this->addArgument(self::ARGUMENT_SSL_MODE, InputArgument::REQUIRED, 'SSL mode (ssl or none)');
		$this->addArgument(self::ARGUMENT_BAUTH_USER, InputArgument::REQUIRED, 'Basic authentication user');
		$this->addArgument(self::ARGUMENT_BAUTH_PASSWORD, InputArgument::REQUIRED, 'Basic authentication password');
		$this->addArgument(self::ARGUMENT_PATH, InputArgument::OPTIONAL, 'JMAP session endpoint path (e.g. /jmap/session)');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument(self::ARGUMENT_USER_ID);
		$name = $input->getArgument(self::ARGUMENT_NAME);
		$email = $input->getArgument(self::ARGUMENT_EMAIL);
		$host = $input->getArgument(self::ARGUMENT_HOST);
		$port = (int)$input->getArgument(self::ARGUMENT_PORT);
		$sslMode = $input->getArgument(self::ARGUMENT_SSL_MODE);
		$bauthUser = $input->getArgument(self::ARGUMENT_BAUTH_USER);
		$bauthPassword = $input->getArgument(self::ARGUMENT_BAUTH_PASSWORD);
		$path = $input->getArgument(self::ARGUMENT_PATH);

		if (!$this->userManager->userExists($userId)) {
			$output->writeln("<error>User $userId does not exist</error>");
			return self::FAILURE;
		}

		$account = new MailAccount();
		$account->setUserId($userId);
		$account->setName($name);
		$account->setEmail($email);
		$account->setProtocol(MailAccount::PROTOCOL_JMAP);
		$account->setInboundHost($host);
		$account->setInboundPort($port);
		$account->setInboundSslMode($sslMode);
		$account->setInboundUser($bauthUser);
		$account->setInboundPassword($this->crypto->encrypt($bauthPassword));
		if ($path !== null) {
			$account->setPath($path);
		}
		$account->setClassificationEnabled($this->classificationSettingsService->isClassificationEnabledByDefault());

		$account = $this->accountService->save($account);

		$output->writeln('<info>JMAP account ' . $account->getId() . " for $email created</info>");

		return self::SUCCESS;
	}
}
