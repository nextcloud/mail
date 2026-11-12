<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use InvalidArgumentException;
use OCA\Mail\Exception\ValidationException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateProvisioning extends ACreateOrUpdateProvisioningCommand {
	protected function configure(): void {
		$this->setName('mail:provisioning:update');
		$this->setDescription('Update a mail account provisioning configuration');
		$this->setHelp(sprintf(
			<<<'EOT'
				Only the values passed as options are changed, everything else keeps the value
				it has. Sieve, the master password and LDAP alias provisioning are switched
				off with <info>--no-sieve</info>, <info>--no-master-password</info> and <info>--no-ldap-aliases</info>.

				%s

				Run <info>mail:provisioning:list</info> to look up the id of a configuration.
				EOT,
			$this->templatesHelp(),
		));
		$this->addUsage('42 --imap-host=imap.example.com --imap-port=993 --imap-ssl-mode=ssl');
		$this->addIdArgument();
		$this->addProvisioningOptions();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$provisioning = $this->findProvisioning($input, $output);
		if (is_int($provisioning)) {
			return $provisioning;
		}
		$id = $provisioning->getId();

		try {
			$data = $this->buildProvisioningData($input, $output, $provisioning->jsonSerialize());
		} catch (InvalidArgumentException $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return self::INVALID;
		}

		try {
			$this->provisioningManager->updateProvisioning($data);
		} catch (ValidationException $e) {
			$output->writeln('<error>' . $this->formatValidationError($e) . '</error>');
			return self::INVALID;
		}

		$output->writeln("<info>Provisioning configuration $id updated</info>");

		return self::SUCCESS;
	}
}
