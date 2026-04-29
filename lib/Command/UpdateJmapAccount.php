<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Security\ICrypto;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateJmapAccount extends Command {
	public const ARGUMENT_ACCOUNT_ID = 'account-id';
	public const ARGUMENT_NAME = 'name';
	public const ARGUMENT_EMAIL = 'email';
	public const ARGUMENT_HOST = 'host';
	public const ARGUMENT_PORT = 'port';
	public const ARGUMENT_SSL_MODE = 'ssl-mode';
	public const ARGUMENT_BAUTH_USER = 'basic-auth-user';
	public const ARGUMENT_BAUTH_PASSWORD = 'basic-auth-password';
	public const ARGUMENT_PATH = 'path';

	public function __construct(
		private readonly MailAccountMapper $mapper,
		private readonly ICrypto $crypto,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('mail:account:update-jmap');
		$this->setDescription('Update a JMAP mail account');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED);

		$this->addOption(self::ARGUMENT_NAME, null, InputOption::VALUE_OPTIONAL);
		$this->addOption(self::ARGUMENT_EMAIL, null, InputOption::VALUE_OPTIONAL);
		$this->addOption(self::ARGUMENT_HOST, null, InputOption::VALUE_OPTIONAL);
		$this->addOption(self::ARGUMENT_PORT, null, InputOption::VALUE_OPTIONAL);
		$this->addOption(self::ARGUMENT_SSL_MODE, null, InputOption::VALUE_OPTIONAL);
		$this->addOption(self::ARGUMENT_BAUTH_USER, null, InputOption::VALUE_OPTIONAL);
		$this->addOption(self::ARGUMENT_BAUTH_PASSWORD, null, InputOption::VALUE_OPTIONAL);
		$this->addOption(self::ARGUMENT_PATH, null, InputOption::VALUE_OPTIONAL);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);

		$name = $input->getOption(self::ARGUMENT_NAME);
		$email = $input->getOption(self::ARGUMENT_EMAIL);
		$host = $input->getOption(self::ARGUMENT_HOST);
		$port = $input->getOption(self::ARGUMENT_PORT);
		$sslMode = $input->getOption(self::ARGUMENT_SSL_MODE);
		$basicAuthUser = $input->getOption(self::ARGUMENT_BAUTH_USER);
		$basicAuthPassword = $input->getOption(self::ARGUMENT_BAUTH_PASSWORD);
		$path = $input->getOption(self::ARGUMENT_PATH);

		try {
			$mailAccount = $this->mapper->findById($accountId);
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>No Email Account found with ID $accountId </error>");
			return self::FAILURE;
		}

		if ($mailAccount->getProtocol() !== MailAccount::PROTOCOL_JMAP) {
			$output->writeln('<error>Account ' . $accountId . ' uses protocol ' . $mailAccount->getProtocol() . '. Use mail:account:update-imap instead.</error>');
			return self::FAILURE;
		}

		$output->writeln('<info>Found JMAP account with email: ' . $mailAccount->getEmail() . '</info>');

		if ($input->getOption(self::ARGUMENT_NAME) !== null) {
			$mailAccount->setName($name);
		}
		if ($input->getOption(self::ARGUMENT_EMAIL) !== null) {
			$mailAccount->setEmail($email);
		}
		if ($input->getOption(self::ARGUMENT_HOST) !== null) {
			$mailAccount->setInboundHost($host);
		}
		if ($input->getOption(self::ARGUMENT_PORT) !== null) {
			$mailAccount->setInboundPort((int)$port);
		}
		if ($input->getOption(self::ARGUMENT_SSL_MODE) !== null) {
			$mailAccount->setInboundSslMode($sslMode);
		}
		if ($input->getOption(self::ARGUMENT_BAUTH_USER) !== null) {
			$mailAccount->setInboundUser($basicAuthUser);
		}
		if ($input->getOption(self::ARGUMENT_BAUTH_PASSWORD) !== null) {
			$mailAccount->setInboundPassword($this->crypto->encrypt($basicAuthPassword));
		}
		if ($input->getOption(self::ARGUMENT_PATH) !== null) {
			$mailAccount->setPath($path);
		}

		$this->mapper->save($mailAccount);

		$output->writeln('<info>JMAP account ' . $mailAccount->getEmail() . " with ID $accountId succesfully updated </info>");
		return self::SUCCESS;
	}
}