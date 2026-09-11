<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ProvisionAccounts extends Command {
	public function __construct(
		private readonly ProvisioningManager $provisioningManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('mail:provisioning:apply');
		$this->setDescription('Apply the provisioning configurations to all users');
		$this->setHelp(
			<<<'EOT'
				Walks through all users and creates or updates the mail account of everyone
				matching a provisioning configuration, instead of waiting for each user to open
				Mail. Users who lost access to Mail lose their provisioned account. Existing
				accounts are kept when their owner no longer matches any configuration.

				This takes no options. Configurations are managed with
				<info>mail:provisioning:create</info> and <info>mail:provisioning:update</info>.

				The IMAP, SMTP and Sieve password can only be stored while the user is logged
				in, so provisioned accounts start syncing once their owner opens Mail.
				EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$count = $this->provisioningManager->provision();

		$output->writeln("<info>Provisioned $count accounts</info>");

		return self::SUCCESS;
	}
}
