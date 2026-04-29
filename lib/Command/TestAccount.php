<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use Horde_Imap_Client;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Exception;
use OCA\Mail\Account;
use OCA\Mail\AddressList;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\JMAP\JmapOperationsService;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function array_slice;
use function array_values;
use function array_keys;
use function in_array;
use function count;
use function date;
use function json_decode;
use function max;
use function mb_strimwidth;
use function microtime;
use function round;
use function sort;
use function strtolower;
use function usort;

final class TestAccount extends Command {
	private const ARGUMENT_ACCOUNT_ID = 'account-id';
	private const OPTION_MAILBOX_LIMIT = 'mailboxes';
	private const OPTION_MESSAGE_LIMIT = 'messages';
	private const DEFAULT_MAILBOX_LIMIT = 10;
	private const DEFAULT_MESSAGE_LIMIT = 5;

	public function __construct(
		private AccountService $accountService,
		private ProtocolFactory $protocolFactory,
		private FolderMapper $folderMapper,
		private ImapMessageMapper $imapMessageMapper,
		private JmapOperationsService $jmapOperationsService,
		private LoggerInterface $logger,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('mail:account:test');
		$this->setAliases(['mail:account:diagnose']);
		$this->setDescription('Test the connection for a mail account (IMAP or JMAP)');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED, 'The ID of the mail account');
		$this->addOption(self::OPTION_MAILBOX_LIMIT, null, InputOption::VALUE_OPTIONAL, 'Number of mailboxes to list', (string)self::DEFAULT_MAILBOX_LIMIT);
		$this->addOption(self::OPTION_MESSAGE_LIMIT, null, InputOption::VALUE_OPTIONAL, 'Number of recent inbox messages to list', (string)self::DEFAULT_MESSAGE_LIMIT);
		$this->setHelp(<<<'HELP'
			The <info>mail:account:test</info> command checks connectivity for a stored
			mail account and prints protocol-specific diagnostics, mailbox listings,
			and a short inbox preview.

			Examples:

			  Test an account by ID:
			    <info>php occ mail:account:test 42</info>

			  Limit the mailbox and inbox preview output:
			    <info>php occ mail:account:test 42 --mailboxes=5 --messages=3</info>

			  Use the legacy alias:
			    <info>php occ mail:account:diagnose 42</info>

			The command detects whether the account uses IMAP or JMAP and prints the
			corresponding endpoint, authentication context, latency, capabilities,
			mailboxes, and recent inbox messages.
			HELP);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$io = new SymfonyStyle($input, $output);
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);
		$mailboxLimit = max(1, (int)$input->getOption(self::OPTION_MAILBOX_LIMIT));
		$messageLimit = max(1, (int)$input->getOption(self::OPTION_MESSAGE_LIMIT));

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$io->error("Account $accountId does not exist");
			return self::FAILURE;
		}

		$protocol = $account->getMailAccount()->getProtocol();
		$this->renderAccountSummary($account, $io);

		return match ($protocol) {
			MailAccount::PROTOCOL_IMAP => $this->testImap($account, $io, $mailboxLimit, $messageLimit),
			MailAccount::PROTOCOL_JMAP => $this->testJmap($account, $io, $mailboxLimit, $messageLimit),
			default => $this->unsupportedProtocol($protocol, $io),
		};
	}

	private function renderAccountSummary(Account $account, SymfonyStyle $io): void {
		$mailAccount = $account->getMailAccount();

		$io->title('Mail Account Connection Test');
		$io->definitionList(
			['Account ID' => (string)$account->getId()],
			['Email' => $account->getEmail()],
			['Name' => $account->getName()],
			['Protocol' => $mailAccount->getProtocol()],
			['Authentication' => $mailAccount->getAuthMethod()],
		);
	}

	private function testImap(Account $account, SymfonyStyle $io, int $mailboxLimit, int $messageLimit): int {
		$io->section('IMAP Test');

		$mailAccount = $account->getMailAccount();
		$sslMode = $mailAccount->getInboundSslMode();
		$scheme = ($sslMode === 'none') ? 'imap' : 'imaps';
		$host = $mailAccount->getInboundHost() ?? '(not set)';
		$port = $mailAccount->getInboundPort();
		$io->definitionList(
			['Server' => $scheme . '://' . $host . ':' . $port],
			['Username' => $mailAccount->getInboundUser()],
			['Security' => $sslMode],
		);

		if ($account->getMailAccount()->getInboundPassword() === null) {
			$io->error('No IMAP password set. The user may need to log in to set it.');
			return self::FAILURE;
		}

		$io->text('Opening IMAP connection...');

		try {
			$imapClient = $this->protocolFactory->imapClient($account);
		} catch (\Exception $e) {
			$io->error('Could not create IMAP client: ' . $e->getMessage());
			return self::FAILURE;
		}

		$startTime = microtime(true);
		try {
			$imapClient->login();
			$latency = (int)round(max(0, microtime(true) - $startTime) * 1000);
			$mailboxes = $this->folderMapper->getFolders($account, $imapClient);
			$this->folderMapper->fetchFolderAcls($mailboxes, $imapClient);

			$capabilities = array_keys(
				json_decode($imapClient->capability->serialize(), true, 512, JSON_THROW_ON_ERROR)
			);
			sort($capabilities);

			$io->success('IMAP connection test passed.');
			$io->definitionList(
				['Login' => 'Successful'],
				['Latency' => $latency . ' ms'],
				['Capabilities' => (string)count($capabilities)],
			);

			if ($capabilities === []) {
				$io->note('The server returned no CAPABILITY entries.');
			} else {
				$io->listing($capabilities);
			}

			$this->renderImapMailboxPreview($account, $imapClient, $mailboxes, $io, $mailboxLimit, $messageLimit);

			return self::SUCCESS;
		} catch (Horde_Imap_Client_Exception $e) {
			$this->logger->error('IMAP connection test failed for account ' . $account->getId() . ': ' . $e->getMessage(), [
				'exception' => $e,
			]);
			$io->error('IMAP connection test failed: ' . $e->getMessage());
			return self::FAILURE;
		} finally {
			$imapClient->logout();
		}
	}

	private function testJmap(Account $account, SymfonyStyle $io, int $mailboxLimit, int $messageLimit): int {
		$io->section('JMAP Test');

		$mailAccount = $account->getMailAccount();
		$sslMode = $mailAccount->getInboundSslMode();
		$scheme = ($sslMode === 'none') ? 'http' : 'https';
		$host = $mailAccount->getInboundHost() ?? '(not set)';
		$port = $mailAccount->getInboundPort();
		$path = $mailAccount->getPath() ?? '/.well-known/jmap';
		$io->definitionList(
			['Server' => $scheme . '://' . $host . ':' . $port . $path],
			['Username' => $mailAccount->getInboundUser()],
			['Security' => $sslMode],
		);

		if ($mailAccount->getInboundPassword() === null) {
			$io->error('No JMAP password set. The user may need to log in to set it.');
			return self::FAILURE;
		}

		$io->text('Opening JMAP session...');
		$startTime = microtime(true);

		try {
			$client = $this->protocolFactory->jmapClient($account);
			$session = $client->connect();
			$this->jmapOperationsService->connect($account);
		} catch (\Exception $e) {
			$this->logger->error('JMAP connection test failed for account ' . $account->getId() . ': ' . $e->getMessage(), [
				'exception' => $e,
			]);
			$io->error('JMAP connection test failed: ' . $e->getMessage());
			return self::FAILURE;
		}

		if (!$client->sessionStatus()) {
			$io->error('JMAP session discovery failed. Check the server and credentials.');
			return self::FAILURE;
		}

		$latency = (int)round(max(0, microtime(true) - $startTime) * 1000);

		$io->success('JMAP connection test passed.');
		$io->definitionList(
			['Session' => 'Established'],
			['Username' => $session->username()],
			['API URL' => $session->commandUrl()],
			['State' => $session->state()],
			['Latency' => $latency . ' ms'],
		);

		$capabilities = [];
		foreach ($session->capabilities() as $capability) {
			$capabilities[] = $capability->id();
		}
		sort($capabilities);

		if ($capabilities === []) {
			$io->note('The server returned no JMAP capabilities.');
		} else {
			$io->listing($capabilities);
		}

		$this->renderJmapMailboxPreview($io, $mailboxLimit, $messageLimit);

		return self::SUCCESS;
	}

	/**
	 * @param list<\OCA\Mail\Folder> $folders
	 */
	private function renderImapMailboxPreview(Account $account, $imapClient, array $folders, SymfonyStyle $io, int $mailboxLimit, int $messageLimit): void {
		$io->section('Mailboxes');

		usort($folders, static fn ($left, $right) => strcmp($left->getMailbox(), $right->getMailbox()));
		$rows = [];
		foreach (array_slice($folders, 0, $mailboxLimit) as $folder) {
			$status = $this->folderMapper->getFolderStatus($imapClient, $folder->getMailbox());
			$attributes = array_map(static fn (string $attribute) => strtolower($attribute), $folder->getAttributes());
			$rows[] = [
				$folder->getMailbox(),
				$folder->getDelimiter() ?? 'NIL',
				in_array('\\noselect', $attributes, true) ? 'no' : 'yes',
				$status !== null ? (string)$status->getTotal() : 'N/A',
				$status !== null ? (string)$status->getUnread() : 'N/A',
			];
		}

		if ($rows === []) {
			$io->note('No mailboxes returned by the IMAP server.');
		} else {
			$io->table(['Mailbox', 'Delimiter', 'Selectable', 'Messages', 'Unseen'], $rows);
			if (count($folders) > $mailboxLimit) {
				$io->note('Showing the first ' . $mailboxLimit . ' mailboxes. Increase --mailboxes to see more.');
			}
		}

		$io->section('Inbox Preview');
		$inbox = array_values(array_filter($folders, static fn ($folder) => strtolower($folder->getMailbox()) === 'inbox'))[0] ?? null;
		if ($inbox === null) {
			$io->note('No INBOX mailbox returned by the IMAP server.');
			return;
		}

		try {
			$messages = $this->loadRecentImapInboxMessages($account, $imapClient, $inbox->getMailbox(), $messageLimit);
		} catch (\Throwable $e) {
			$this->logger->warning('Could not load IMAP inbox preview for account ' . $account->getId() . ': ' . $e->getMessage(), [
				'exception' => $e,
			]);
			$io->warning('Connected successfully, but could not load recent inbox messages: ' . $e->getMessage());
			return;
		}

		$this->renderMessageTable($io, $this->buildImapMessageRows($messages), 'No recent messages found in INBOX.');
	}

	/**
	 * @return list<IMAPMessage>
	 */
	private function loadRecentImapInboxMessages(Account $account, $imapClient, string $mailbox, int $messageLimit): array {
		$metaResults = $imapClient->search(
			$mailbox,
			null,
			[
				'results' => [
					Horde_Imap_Client::SEARCH_RESULTS_MIN,
					Horde_Imap_Client::SEARCH_RESULTS_MAX,
					Horde_Imap_Client::SEARCH_RESULTS_COUNT,
				],
			]
		);

		$total = (int)($metaResults['count'] ?? 0);
		if ($total === 0) {
			return [];
		}

		$maxUid = $metaResults['max'];
		if ($maxUid === null) {
			$status = $imapClient->status($mailbox);
			$maxUid = ((int)($status['uidnext'] ?? 1)) - 1;
		}

		$lower = max(1, (int)$maxUid - max(50, $messageLimit * 20));
		$uids = new Horde_Imap_Client_Ids($lower . ':' . (int)$maxUid);
		$messages = $this->imapMessageMapper->findByIds($imapClient, $mailbox, $uids, $account->getUserId(), false);

		usort($messages, static fn (IMAPMessage $left, IMAPMessage $right) => $right->getSentDate()->getTimestamp() <=> $left->getSentDate()->getTimestamp());

		return array_slice($messages, 0, $messageLimit);
	}

	private function renderJmapMailboxPreview(SymfonyStyle $io, int $mailboxLimit, int $messageLimit): void {
		$io->section('Mailboxes');
		$mailboxes = $this->jmapOperationsService->collectionList(null, null, [
			['attribute' => 'order', 'direction' => true],
			['attribute' => 'name', 'direction' => true],
		]);

		$rows = [];
		foreach (array_slice($mailboxes, 0, $mailboxLimit) as $mailbox) {
			$rows[] = [
				$mailbox->getName(),
				$mailbox->getDelimiter() ?? 'NIL',
				$mailbox->getSelectable() ? 'yes' : 'no',
				(string)$mailbox->getMessages(),
				(string)$mailbox->getUnseen(),
			];
		}

		if ($rows === []) {
			$io->note('No mailboxes returned by the JMAP server.');
		} else {
			$io->table(['Mailbox', 'Delimiter', 'Selectable', 'Messages', 'Unseen'], $rows);
			if (count($mailboxes) > $mailboxLimit) {
				$io->note('Showing the first ' . $mailboxLimit . ' mailboxes. Increase --mailboxes to see more.');
			}
		}

		$io->section('Inbox Preview');
		$inbox = $this->findInboxMailbox($mailboxes);
		if ($inbox === null || $inbox->getRemoteId() === null) {
			$io->note('No INBOX mailbox returned by the JMAP server.');
			return;
		}

		$messages = $this->jmapOperationsService->entityList(
			$inbox->getRemoteId(),
			null,
			[['attribute' => 'received', 'direction' => true]],
			['anchor' => 'absolute', 'position' => 0, 'tally' => $messageLimit]
		);

		/** @var list<Message> $messageList */
		$messageList = $messages['list'] ?? [];
		$this->renderMessageTable($io, $this->buildJmapMessageRows($messageList), 'No recent messages found in INBOX.');
	}

	/**
	 * @param Mailbox[] $mailboxes
	 */
	private function findInboxMailbox(array $mailboxes): ?Mailbox {
		foreach ($mailboxes as $mailbox) {
			if ($mailbox->isSpecialUse('inbox') || $mailbox->isInbox()) {
				return $mailbox;
			}
		}

		return null;
	}

	/**
	 * @param list<IMAPMessage> $messages
	 * @return list<array<int, string>>
	 */
	private function buildImapMessageRows(array $messages): array {
		$rows = [];
		foreach ($messages as $message) {
			$rows[] = [
				(string)$message->getUid(),
				date('Y-m-d H:i', $message->getSentDate()->getTimestamp()),
				$this->formatAddressList($message->getFrom()),
				$this->truncate($message->getSubject()),
				'',
			];
		}

		return $rows;
	}

	/**
	 * @param list<Message> $messages
	 * @return list<array<int, string>>
	 */
	private function buildJmapMessageRows(array $messages): array {
		$rows = [];
		foreach ($messages as $message) {
			$rows[] = [
				(string)($message->getRemoteId() ?? $message->getUid()),
				date('Y-m-d H:i', $message->getSentAt()),
				$this->formatAddressList($message->getFrom()),
				$this->truncate($message->getSubject()),
				$this->truncate($message->getPreviewText() ?? ''),
			];
		}

		return $rows;
	}

	/**
	 * @param list<array<int, string>> $rows
	 */
	private function renderMessageTable(SymfonyStyle $io, array $rows, string $emptyMessage): void {
		if ($rows === []) {
			$io->note($emptyMessage);
			return;
		}

		$io->table(['UID', 'Date', 'From', 'Subject', 'Preview'], $rows);
	}

	private function formatAddressList(AddressList $addresses): string {
		$first = $addresses->first();
		if ($first === null) {
			return 'NIL';
		}

		return $first->getLabel() ?? $first->getEmail() ?? 'NIL';
	}

	private function truncate(string $value, int $length = 60): string {
		if ($value === '') {
			return '';
		}

		return mb_strimwidth($value, 0, $length, '...');
	}

	private function unsupportedProtocol(string $protocol, SymfonyStyle $io): int {
		$io->error("Unsupported protocol: $protocol");
		return self::FAILURE;
	}
}
