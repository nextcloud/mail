<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Command;

use OCA\Mail\Service\AccountService;
use OCP\Security\ICrypto;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportAccount extends Command {
	public const ARGUMENT_USER_ID = 'user-id';

	private AccountService $accountService;
	private ICrypto $crypto;

	public function __construct(AccountService $service, ICrypto $crypto) {
		parent::__construct();

		$this->accountService = $service;
		$this->crypto = $crypto;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('mail:account:export');
		$this->setDescription('Exports a user\'s IMAP account(s)');
		$this->addArgument(self::ARGUMENT_USER_ID, InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument(self::ARGUMENT_USER_ID);

		$accounts = $this->accountService->findByUserId($userId);

		foreach ($accounts as $account) {
			$output->writeln("<info>Account " . $account->getId() . ":</info>");
			$output->writeln("- E-Mail: " . $account->getEmail());
			$output->writeln("- Name: " . $account->getName());
			$output->writeln("- Provision: " . ($account->getMailAccount()->getProvisioningId() ? "set" : "none"). " ID: " . ($account->getMailAccount()->getProvisioningId() ? $account->getMailAccount()->getProvisioningId():"N/A"));
			$output->writeln("- IMAP user: " . $account->getMailAccount()->getInboundUser());
			$output->writeln("- IMAP host: " . $account->getMailAccount()->getInboundHost() . ":" . $account->getMailAccount()->getInboundPort() . ", security: " . $account->getMailAccount()->getInboundSslMode());
			$output->writeln("- SMTP user: " . $account->getMailAccount()->getOutboundUser());
			$output->writeln("- SMTP host: " . $account->getMailAccount()->getOutboundHost() . ":" . $account->getMailAccount()->getOutboundPort() . ", security: " . $account->getMailAccount()->getOutboundSslMode());
		}

		return 0;
	}
}
