<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class DeleteProvisioning extends Command {
	public const ARGUMENT_ID = 'id';
	public const OPTION_FORCE = 'force';

	public function __construct(
		private readonly ProvisioningManager $provisioningManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('mail:provisioning:delete');
		$this->setDescription('Delete a mail account provisioning configuration and all accounts provisioned with it');
		$this->setHelp(
			<<<'EOT'
				Deleting a configuration also deletes every mail account that was provisioned
				with it, including the locally cached messages. Mail accounts users created
				themselves are not affected.

				Run <info>mail:provisioning:list</info> to look up the id of a configuration.
				EOT
		);
		$this->addUsage('42 --force');
		$this->addArgument(self::ARGUMENT_ID, InputArgument::REQUIRED, 'Id of the provisioning configuration');
		$this->addOption(self::OPTION_FORCE, 'f', InputOption::VALUE_NONE, 'Delete without asking for confirmation');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$id = filter_var($input->getArgument(self::ARGUMENT_ID), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		if ($id === false) {
			$output->writeln('<error>Provisioning configuration id must be a positive integer</error>');
			return self::INVALID;
		}

		$provisioning = $this->provisioningManager->getConfigById($id);
		if ($provisioning === null) {
			$output->writeln("<error>Provisioning configuration $id does not exist</error>");
			return self::FAILURE;
		}

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
