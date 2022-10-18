<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Maadix
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Command;

use OCA\Mail\Db\MailAccountMapper;
use OCP\Security\ICrypto;
use OCP\AppFramework\Db\DoesNotExistException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAccount extends Command {
	public const ARGUMENT_ACCOUNT_ID = 'account-id';
	public const ARGUMENT_NAME = 'name';
	public const ARGUMENT_EMAIL = 'email';
	public const ARGUMENT_AUTH_METHOD = 'auth-method';
	public const ARGUMENT_IMAP_HOST = 'imap-host';
	public const ARGUMENT_IMAP_PORT = 'imap-port';
	public const ARGUMENT_IMAP_SSL_MODE = 'imap-ssl-mode';
	public const ARGUMENT_IMAP_USER = 'imap-user';
	public const ARGUMENT_IMAP_PASSWORD = 'imap-password';
	public const ARGUMENT_SMTP_HOST = 'smtp-host';
	public const ARGUMENT_SMTP_PORT = 'smtp-port';
	public const ARGUMENT_SMTP_SSL_MODE = 'smtp-ssl-mode';
	public const ARGUMENT_SMTP_USER = 'smtp-user';
	public const ARGUMENT_SMTP_PASSWORD = 'smtp-password';


	/** @var mapper */
	private $mapper;

	/** @var ICrypto */
	private $crypto;

	public function __construct(MailAccountMapper $mapper, ICrypto $crypto) {
		parent::__construct();

		$this->mapper = $mapper;
		$this->crypto = $crypto;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('mail:account:update');
		$this->setDescription('Update a user\'s IMAP account');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED);

		$this->addOption(self::ARGUMENT_NAME, '', InputOption::VALUE_OPTIONAL);
		$this->addOption(self::ARGUMENT_EMAIL, '', InputOption::VALUE_OPTIONAL);

		$this->addOption(self::ARGUMENT_IMAP_HOST, '', InputOption::VALUE_OPTIONAL);
		$this->addOption(self::ARGUMENT_IMAP_PORT, '', InputOption::VALUE_OPTIONAL);
		$this->addOption(self::ARGUMENT_IMAP_SSL_MODE, '', InputOption::VALUE_OPTIONAL);
		$this->addOption(self::ARGUMENT_IMAP_USER, '', InputOption::VALUE_OPTIONAL);
		$this->addOption(self::ARGUMENT_IMAP_PASSWORD, '', InputOption::VALUE_OPTIONAL);

		$this->addOption(self::ARGUMENT_SMTP_HOST, '', InputOption::VALUE_OPTIONAL);
		$this->addOption(self::ARGUMENT_SMTP_PORT, '', InputOption::VALUE_OPTIONAL);
		$this->addOption(self::ARGUMENT_SMTP_SSL_MODE, '', InputOption::VALUE_OPTIONAL);
		$this->addOption(self::ARGUMENT_SMTP_USER, '', InputOption::VALUE_OPTIONAL);
		$this->addOption(self::ARGUMENT_SMTP_PASSWORD, '', InputOption::VALUE_OPTIONAL);

		$this->addOption(self::ARGUMENT_AUTH_METHOD, '', InputOption::VALUE_OPTIONAL);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);

		$name = $input->getOption(self::ARGUMENT_NAME);
		$email = $input->getOption(self::ARGUMENT_EMAIL);

		$imapHost = $input->getOption(self::ARGUMENT_IMAP_HOST);
		$imapPort = $input->getOption(self::ARGUMENT_IMAP_PORT);
		$imapSslMode = $input->getOption(self::ARGUMENT_IMAP_SSL_MODE);
		$imapUser = $input->getOption(self::ARGUMENT_IMAP_USER);
		$imapPassword = $input->getOption(self::ARGUMENT_IMAP_PASSWORD);

		$smtpHost = $input->getOption(self::ARGUMENT_SMTP_HOST);
		$smtpPort = $input->getOption(self::ARGUMENT_SMTP_PORT);
		$smtpSslMode = $input->getOption(self::ARGUMENT_SMTP_SSL_MODE);
		$smtpUser = $input->getOption(self::ARGUMENT_SMTP_USER);
		$smtpPassword = $input->getOption(self::ARGUMENT_SMTP_PASSWORD);
		$authMethod = $input->getOption(self::ARGUMENT_AUTH_METHOD);

		try {
			$mailAccount = $this->mapper->findById($accountId);
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>No Email Account found with ID $accountId </error>");
			return 1;
		}

		$output->writeLn("<info>Found account with email: " . $mailAccount->getEmail() . "</info>");
			
		//AUTH METHOD
		if ($input->getOption(self::ARGUMENT_AUTH_METHOD)) {
			$mailAccount->setAuthMethod($authMethod);
		}

		//ACCOUNT OPTIONS
		if ($input->getOption(self::ARGUMENT_NAME)) {
			$mailAccount->setName($name);
		}
		if ($input->getOption(self::ARGUMENT_EMAIL)) {
			$mailAccount->setEmail($email);
		}

		//INBOUND
		if ($input->getOption(self::ARGUMENT_IMAP_HOST)) {
			$mailAccount->setInboundHost($imapHost);
		}

		if ($input->getOption(self::ARGUMENT_IMAP_PORT)) {
			$mailAccount->setInboundPort((int) $imapPort);
		}

		if ($input->getOption(self::ARGUMENT_IMAP_SSL_MODE)) {
			$mailAccount->setInboundSslMode($imapSslMode);
		}

		if ($input->getOption(self::ARGUMENT_IMAP_PASSWORD)) {
			$mailAccount->setInboundPassword($this->crypto->encrypt($imapPassword));
		}

		if ($input->getOption(self::ARGUMENT_SMTP_USER)) {
			$mailAccount->setInboundUser($imapUser);
		}

		// OUTBOUND

		if ($input->getOption(self::ARGUMENT_SMTP_HOST)) {
			$mailAccount->setOutboundHost($smtpHost);
		}

		if ($input->getOption(self::ARGUMENT_SMTP_PORT)) {
			$mailAccount->setOutboundPort((int) $smtpPort);
		}

		if ($input->getOption(self::ARGUMENT_SMTP_SSL_MODE)) {
			$mailAccount->setOutboundSslMode($smtpSslMode);
		}

		if ($input->getOption(self::ARGUMENT_SMTP_PASSWORD)) {
			$mailAccount->setOutboundPassword($this->crypto->encrypt($smtpPassword));
		}

		if ($input->getOption(self::ARGUMENT_SMTP_USER)) {
			$mailAccount->setOutboundUser($smtpUser);
		}

		$this->mapper->save($mailAccount);

		$output->writeln("<info>Account " . $mailAccount->getEmail() . " with ID  $accountId  succesfully updated </info>");
		return 0;
	}
}
