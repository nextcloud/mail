<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Db\Provisioning;
use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base of the provisioning configuration commands, including addressing an
 * existing configuration by its id.
 */
abstract class AProvisioningCommand extends Command {
	public const ARGUMENT_ID = 'id';

	public function __construct(
		protected readonly ProvisioningManager $provisioningManager,
	) {
		parent::__construct();
	}

	protected function addIdArgument(): void {
		$this->addArgument(self::ARGUMENT_ID, InputArgument::REQUIRED, 'Id of the provisioning configuration');
	}

	/**
	 * @return Provisioning|int the configuration, or the exit code after the error was written
	 */
	protected function findProvisioning(InputInterface $input, OutputInterface $output): Provisioning|int {
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

		return $provisioning;
	}
}
