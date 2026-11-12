<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class DeleteProvisioning extends AProvisioningCommand {
	public const OPTION_FORCE = 'force';

	protected function configure(): void {
		$this->setName('mail:provisioning:delete');
		$this->setDescription('Delete a mail account provisioning configuration and all accounts provisioned with it');
		$this->setHelp(
			<<<'EOT'
				Deleting a configuration also deletes every mail account that was provisioned
				with it. Mail accounts users created themselves are not affected.

				The locally cached mailboxes and messages of the deleted accounts are removed
				by the daily cleanup background job. Run <info>mail:clean-up</info> to remove
				them right away.

				Run <info>mail:provisioning:list</info> to look up the id of a configuration.
				EOT
		);
		$this->addUsage('42 --force');
		$this->addIdArgument();
		$this->addOption(self::OPTION_FORCE, 'f', InputOption::VALUE_NONE, 'Delete without asking for confirmation');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$provisioning = $this->findProvisioning($input, $output);
		if (is_int($provisioning)) {
			return $provisioning;
		}
		$id = $provisioning->getId();

		if (!$input->getOption(self::OPTION_FORCE)) {
			$question = new ConfirmationQuestion(
				"Delete configuration $id and all mail accounts provisioned with it? [y/N] ",
				false,
			);
			if (!$this->getHelper('question')->ask($input, $output, $question)) {
				$output->writeln('Aborted');
				return self::SUCCESS;
			}
		}

		$this->provisioningManager->deprovision($provisioning);

		$output->writeln("<info>Provisioning configuration $id deleted</info>");

		return self::SUCCESS;
	}
}
