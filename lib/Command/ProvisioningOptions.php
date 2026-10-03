<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Shared option definitions of the provisioning configuration commands.
 *
 * @psalm-type ProvisioningData = array<string, mixed>
 */
trait ProvisioningOptions {
	private function templatesHelp(): string {
		return <<<'EOT'
			The account email address and the IMAP, SMTP and Sieve logins are templates. They
			may contain %USERID%, %EMAIL% and %LDAP:attribute%, which are replaced with the
			values of each provisioned user.

			Unless a master password is set, the login password of the user is used for IMAP,
			SMTP and Sieve. It is stored when the user opens Mail and updated whenever it
			changes, so an admin never has to know it.
			EOT;
	}

	private function addProvisioningOptions(): void {
		foreach (ProvisioningOption::FIELDS as $option => [, $description]) {
			$this->addOption($option, null, InputOption::VALUE_REQUIRED, $description);
		}

		$this->addOption(
			ProvisioningOption::MASTER_PASSWORD,
			null,
			InputOption::VALUE_NONE,
			'Use a master password for all accounts instead of the login password, read from the prompt or stdin',
		);
		$this->addOption(ProvisioningOption::NO_MASTER_PASSWORD, null, InputOption::VALUE_NONE, 'Use the login password of each user');
		$this->addOption(ProvisioningOption::NO_SIEVE, null, InputOption::VALUE_NONE, 'Disable Sieve');
		$this->addOption(ProvisioningOption::NO_LDAP_ALIASES, null, InputOption::VALUE_NONE, 'Disable alias provisioning from LDAP');
	}

	/**
	 * @param array<string, mixed> $defaults values of the configuration being edited, empty when creating a new one
	 * @return array<string, mixed>
	 * @throws InvalidArgumentException
	 */
	private function buildProvisioningData(InputInterface $input, OutputInterface $output, array $defaults = []): array {
		$data = $defaults;
		foreach (ProvisioningOption::FIELDS as $option => [$key]) {
			$value = $input->getOption($option);
			if ($value !== null) {
				$data[$key] = $value;
			}
		}

		$data['sievePort'] = isset($data['sievePort']) && $data['sievePort'] !== '' ? $data['sievePort'] : null;
		foreach (['imapPort', 'smtpPort', 'sievePort'] as $key) {
			if (isset($data[$key])) {
				$port = filter_var($data[$key], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
				if ($port === false) {
					throw new InvalidArgumentException($key . ' must be an integer between 1 and 65535');
				}
				$data[$key] = $port;
			}
		}

		foreach (['imapSslMode', 'smtpSslMode', 'sieveSslMode'] as $key) {
			$sslMode = $data[$key] ?? '';
			if ($sslMode !== '' && !in_array($sslMode, ProvisioningOption::SSL_MODES, true)) {
				throw new InvalidArgumentException($key . ' must be one of ' . implode(', ', ProvisioningOption::SSL_MODES));
			}
		}

		$data['sieveEnabled'] = $this->resolveToggle(
			$input->getOption(ProvisioningOption::NO_SIEVE),
			$input->getOption('sieve-host'),
			$defaults['sieveEnabled'] ?? false,
		);
		if ($data['sieveEnabled'] && $data['sievePort'] === null) {
			throw new InvalidArgumentException('sievePort is required when Sieve is enabled');
		}
		$data['ldapAliasesProvisioning'] = $this->resolveToggle(
			$input->getOption(ProvisioningOption::NO_LDAP_ALIASES),
			$input->getOption('ldap-aliases-attribute'),
			$defaults['ldapAliasesProvisioning'] ?? false,
		);

		if ($input->getOption(ProvisioningOption::NO_MASTER_PASSWORD)) {
			$data['masterPasswordEnabled'] = false;
			$data['masterPassword'] = '';
			$data['masterUser'] = '';
		} elseif ($input->getOption(ProvisioningOption::MASTER_PASSWORD)) {
			$data['masterPasswordEnabled'] = true;
			$data['masterPassword'] = $this->askMasterPassword($input, $output);
		}

		return $data;
	}

	/**
	 * A feature is switched on by passing the value it depends on and off by its
	 * negating flag. Without either the stored state wins.
	 */
	private function resolveToggle(bool $disable, ?string $value, bool $default): bool {
		if ($disable) {
			return false;
		}
		if ($value !== null) {
			return $value !== '';
		}

		return $default;
	}

	private function askMasterPassword(InputInterface $input, OutputInterface $output): string {
		if (!$input->isInteractive()) {
			$stream = $input instanceof StreamableInputInterface ? $input->getStream() : null;
			$password = fgets($stream ?? STDIN);
			if ($password === false) {
				throw new InvalidArgumentException('Could not read master password from stdin');
			}

			return rtrim($password, "\r\n");
		}

		$question = new Question('Master password: ');
		$question->setHidden(true);
		$question->setHiddenFallback(false);
		$question->setTrimmable(false);

		return rtrim((string)$this->getHelper('question')->ask($input, $output, $question), "\r\n");
	}
}
