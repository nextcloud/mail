<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use InvalidArgumentException;
use OCA\Mail\Exception\ValidationException;
use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CreateProvisioning extends Command {
	use ProvisioningOptions;

	public function __construct(
		private readonly ProvisioningManager $provisioningManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('mail:provisioning:create');
		$this->setDescription('Create a mail account provisioning configuration');
		$this->setHelp(sprintf(
			<<<'EOT'
				A provisioning configuration creates and maintains the mail account of every
				user whose email address matches its domain. Pass * as the domain to match all
				users.

				All options are required, except the Sieve, master password and LDAP alias ones.

				%s

				New accounts are provisioned when a user opens Mail. Run
				<info>mail:provisioning:apply</info> to provision all users right away.
				EOT,
			$this->templatesHelp(),
		));
		$this->addUsage(
			"--provisioning-domain='*' --email-template='%USERID%@example.com'"
			. " --imap-user='%USERID%' --imap-host=imap.example.com --imap-port=993 --imap-ssl-mode=ssl"
			. " --smtp-user='%USERID%' --smtp-host=smtp.example.com --smtp-port=587 --smtp-ssl-mode=tls"
		);
		$this->addProvisioningOptions();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$data = $this->buildProvisioningData($input, $output);
		} catch (InvalidArgumentException $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return self::INVALID;
		}

		try {
			$provisioning = $this->provisioningManager->newProvisioning($data);
		} catch (ValidationException $e) {
			$output->writeln('<error>Invalid or missing values: ' . implode(', ', array_keys($e->getFields())) . '</error>');
			return self::INVALID;
		}

		$output->writeln("<info>Provisioning configuration {$provisioning->getId()} created</info>");

		return self::SUCCESS;
	}
}
