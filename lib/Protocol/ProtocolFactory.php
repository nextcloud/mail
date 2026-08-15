<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Protocol;

use Horde_Imap_Client_Socket;
use JmapClient\Client as JmapClient;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailboxConnector;
use OCA\Mail\Contracts\IMessageConnector;
use OCA\Mail\Contracts\ITransmissionConnector;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\JMAP\JmapClientFactory;
use Psr\Container\ContainerInterface;

class ProtocolFactory {

	/**
	 * Maps protocol => connector interface => class name
	 */
	private const CONNECTOR_MAP = [
		// MailAccount::PROTOCOL_IMAP => [
		// 	IMailboxConnector::class => ImapMailboxConnector::class,
		// 	IMessageConnector::class => ImapMessageConnector::class,
		// 	ITransmissionConnector::class => ImapTransmissionConnector::class,
		// ],
		// MailAccount::PROTOCOL_JMAP => [
		// 	IMailboxConnector::class => JmapMailboxConnector::class,
		// 	IMessageConnector::class => JmapMessageConnector::class,
		// 	ITransmissionConnector::class => JmapTransmissionConnector::class,
		// ],
	];

	public function __construct(
		private ContainerInterface $container,
		private IMAPClientFactory $imapClientFactory,
		private JmapClientFactory $jmapClientFactory,
	) {
	}

	/**
	 * @throws ServiceException
	 */
	public function imapClient(Account $account, bool $useCache = true): Horde_Imap_Client_Socket {
		$this->verifyProtocol($account, MailAccount::PROTOCOL_IMAP);
		return $this->imapClientFactory->getClient($account, $useCache);
	}

	/**
	 * @throws ServiceException
	 */
	public function jmapClient(Account $account): JmapClient {
		$this->verifyProtocol($account, MailAccount::PROTOCOL_JMAP);
		return $this->jmapClientFactory->getClient($account);
	}

	/**
	 * @throws ServiceException
	 */
	private function verifyProtocol(Account $account, string $expected): void {
		$actual = $account->getMailAccount()->getProtocol();
		if ($actual !== $expected) {
			throw new ServiceException("Expected protocol $expected but account uses $actual");
		}
	}

	/**
	 * @throws ServiceException
	 */
	public function mailboxConnector(Account $account): IMailboxConnector {
		return $this->resolveConnector($account, IMailboxConnector::class);
	}

	/**
	 * @throws ServiceException
	 */
	public function messageConnector(Account $account): IMessageConnector {
		return $this->resolveConnector($account, IMessageConnector::class);
	}

	/**
	 * @throws ServiceException
	 */
	public function transmissionConnector(Account $account): ITransmissionConnector {
		return $this->resolveConnector($account, ITransmissionConnector::class);
	}

	/**
	 * @template T
	 * @param Account $account
	 * @param class-string<T> $interface
	 * @return T
	 * @throws ServiceException
	 */
	private function resolveConnector(Account $account, string $interface): mixed {
		$protocol = $account->getMailAccount()->getProtocol();
		$class = self::CONNECTOR_MAP[$protocol][$interface] ?? null;

		if ($class === null) {
			throw new ServiceException("No $interface implementation for protocol $protocol");
		}

		return $this->container->get($class);
	}
}
