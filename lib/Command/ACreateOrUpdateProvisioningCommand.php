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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Shared options and input validation of the commands that create or update a
 * provisioning configuration.
 */
abstract class ACreateOrUpdateProvisioningCommand extends AProvisioningCommand {
	private const SSL_MODES = ['none', 'ssl', 'tls'];

	private const OPTION_MASTER_PASSWORD = 'master-password';
	private const OPTION_NO_MASTER_PASSWORD = 'no-master-password';
	private const OPTION_NO_SIEVE = 'no-sieve';
	private const OPTION_NO_LDAP_ALIASES = 'no-ldap-aliases';

	/** Option name => [data key, description] */
	private const FIELDS = [
		'provisioning-domain' => ['provisioningDomain', 'Email domain to provision, or * for all users'],
		'email-template' => ['emailTemplate', 'Account email, supports %USERID%, %EMAIL% and %LDAP:attribute%'],
		'imap-user' => ['imapUser', 'IMAP login, supports the same placeholders as the email template'],
		'imap-host' => ['imapHost', 'IMAP host'],
		'imap-port' => ['imapPort', 'IMAP port'],
		'imap-ssl-mode' => ['imapSslMode', 'IMAP encryption: none, ssl or tls'],
		'smtp-user' => ['smtpUser', 'SMTP login, supports the same placeholders as the email template'],
		'smtp-host' => ['smtpHost', 'SMTP host'],
		'smtp-port' => ['smtpPort', 'SMTP port'],
		'smtp-ssl-mode' => ['smtpSslMode', 'SMTP encryption: none, ssl or tls'],
		'sieve-user' => ['sieveUser', 'Sieve login, required when Sieve is enabled, supports the same placeholders as the email template'],
		'sieve-host' => ['sieveHost', 'Sieve host, enables Sieve when set'],
		'sieve-port' => ['sievePort', 'Sieve port, required when Sieve is enabled'],
		'sieve-ssl-mode' => ['sieveSslMode', 'Sieve encryption: none, ssl or tls'],
		'master-user' => ['masterUser', 'Master user suffix appended to the login, e.g. *masteruser'],
		'ldap-aliases-attribute' => ['ldapAliasesAttribute', 'LDAP attribute to read aliases from, enables alias provisioning when set'],
	];

	protected function templatesHelp(): string {
		return <<<'EOT'
			The account email address and the IMAP, SMTP and Sieve logins are templates. They
			may contain %USERID%, %EMAIL% and %LDAP:attribute%, which are replaced with the
			values of each provisioned user.

			Unless a master password is set, the login password of the user is used for IMAP,
			SMTP and Sieve. It is stored when the user opens Mail and updated whenever it
			changes, so an admin never has to know it.
			EOT;
	}

	protected function addProvisioningOptions(): void {
		foreach (self::FIELDS as $option => [, $description]) {
			$this->addOption($option, null, InputOption::VALUE_REQUIRED, $description);
		}

		$this->addOption(
			self::OPTION_MASTER_PASSWORD,
			null,
			InputOption::VALUE_NONE,
			'Use a master password for all accounts instead of the login password, read from the prompt or stdin',
		);
		$this->addOption(self::OPTION_NO_MASTER_PASSWORD, null, InputOption::VALUE_NONE, 'Use the login password of each user');
		$this->addOption(self::OPTION_NO_SIEVE, null, InputOption::VALUE_NONE, 'Disable Sieve');
		$this->addOption(self::OPTION_NO_LDAP_ALIASES, null, InputOption::VALUE_NONE, 'Disable alias provisioning from LDAP');
	}

	/**
	 * @param array<string, mixed> $defaults values of the configuration being edited, empty when creating a new one
	 * @return array<string, mixed>
	 * @throws InvalidArgumentException
	 */
	protected function buildProvisioningData(InputInterface $input, OutputInterface $output, array $defaults = []): array {
		$this->rejectContradictoryOptions($input);

		$data = $defaults;
		foreach (self::FIELDS as $option => [$key]) {
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
					throw new InvalidArgumentException(self::option($key) . ' must be an integer between 1 and 65535');
				}
				$data[$key] = $port;
			}
		}

		foreach (['imapSslMode', 'smtpSslMode', 'sieveSslMode'] as $key) {
			$sslMode = $data[$key] ?? '';
			if ($sslMode !== '' && !in_array($sslMode, self::SSL_MODES, true)) {
				throw new InvalidArgumentException(self::option($key) . ' must be one of ' . implode(', ', self::SSL_MODES));
			}
		}

		$data['sieveEnabled'] = $this->resolveToggle(
			$input->getOption(self::OPTION_NO_SIEVE),
			$input->getOption('sieve-host'),
			$defaults['sieveEnabled'] ?? false,
		);
		if ($data['sieveEnabled'] && $data['sievePort'] === null) {
			throw new InvalidArgumentException('--sieve-port is required when Sieve is enabled');
		}
		if ($data['sieveEnabled'] && ($data['sieveUser'] ?? '') === '') {
			throw new InvalidArgumentException('--sieve-user is required when Sieve is enabled');
		}
		$data['ldapAliasesProvisioning'] = $this->resolveToggle(
			$input->getOption(self::OPTION_NO_LDAP_ALIASES),
			$input->getOption('ldap-aliases-attribute'),
			$defaults['ldapAliasesProvisioning'] ?? false,
		);

		if ($input->getOption(self::OPTION_NO_MASTER_PASSWORD)) {
			$data['masterPasswordEnabled'] = false;
			$data['masterPassword'] = '';
			$data['masterUser'] = '';
		} elseif ($input->getOption(self::OPTION_MASTER_PASSWORD)) {
			$data['masterPasswordEnabled'] = true;
			$data['masterPassword'] = $this->askMasterPassword($input, $output);
		}

		$masterUser = $input->getOption('master-user');
		if ($masterUser !== null && $masterUser !== '' && !($data['masterPasswordEnabled'] ?? false)) {
			throw new InvalidArgumentException('--master-user requires the master password to be enabled with --' . self::OPTION_MASTER_PASSWORD);
		}

		return $data;
	}

	protected function formatValidationError(ValidationException $e): string {
		return 'Invalid or missing values: ' . implode(', ', array_map(self::option(...), array_keys($e->getFields())));
	}

	/**
	 * Maps a provisioning data key to the option that sets it, so errors name
	 * what the admin typed.
	 */
	private static function option(string $key): string {
		if ($key === 'masterPassword') {
			return '--' . self::OPTION_MASTER_PASSWORD;
		}
		foreach (self::FIELDS as $option => [$field]) {
			if ($field === $key) {
				return '--' . $option;
			}
		}

		return $key;
	}

	/**
	 * Switching a feature on and off in the same call is ambiguous, so neither wins.
	 *
	 * @throws InvalidArgumentException
	 */
	private function rejectContradictoryOptions(InputInterface $input): void {
		$conflicts = [
			[self::OPTION_NO_SIEVE, 'sieve-host'],
			[self::OPTION_NO_LDAP_ALIASES, 'ldap-aliases-attribute'],
		];
		foreach ($conflicts as [$disable, $enable]) {
			$value = $input->getOption($enable);
			if ($input->getOption($disable) && $value !== null && $value !== '') {
				throw new InvalidArgumentException("--$disable cannot be combined with --$enable");
			}
		}

		if ($input->getOption(self::OPTION_NO_MASTER_PASSWORD) && $input->getOption(self::OPTION_MASTER_PASSWORD)) {
			throw new InvalidArgumentException('--' . self::OPTION_NO_MASTER_PASSWORD . ' cannot be combined with --' . self::OPTION_MASTER_PASSWORD);
		}
	}

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
