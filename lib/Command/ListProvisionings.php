<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ListProvisionings extends Command {
	public const OPTION_JSON = 'json';

	public function __construct(
		private readonly ProvisioningManager $provisioningManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('mail:provisioning:list');
		$this->setDescription('List the mail account provisioning configurations');
		$this->setHelp(
			<<<'EOT'
				The table shows a summary of every configuration and the ids needed by
				<info>mail:provisioning:update</info> and <info>mail:provisioning:delete</info>.
				Pass <info>--json</info> to print all values. The master password is never printed.
				EOT
		);
		$this->addOption(self::OPTION_JSON, null, InputOption::VALUE_NONE, 'Print all values as JSON');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$provisionings = $this->provisioningManager->getConfigs();

		if ($input->getOption(self::OPTION_JSON)) {
			$output->writeln(json_encode($provisionings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

			return self::SUCCESS;
		}

		$table = new Table($output);
		$table->setHeaders(['Id', 'Domain', 'Email', 'IMAP', 'SMTP', 'Sieve', 'Master password']);
		foreach ($provisionings as $provisioning) {
			$table->addRow([
				$provisioning->getId(),
				$provisioning->getProvisioningDomain(),
				$provisioning->getEmailTemplate(),
				$provisioning->getImapUser() . '@' . $provisioning->getImapHost() . ':' . $provisioning->getImapPort(),
				$provisioning->getSmtpUser() . '@' . $provisioning->getSmtpHost() . ':' . $provisioning->getSmtpPort(),
				$provisioning->getSieveEnabled() === true ? $provisioning->getSieveHost() . ':' . $provisioning->getSievePort() : 'no',
				$provisioning->getMasterPasswordEnabled() === true ? 'yes' : 'no',
			]);
		}
		$table->render();

		return self::SUCCESS;
	}
}
