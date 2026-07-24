<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Cache\HordeSyncTokenParser;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InspectMailbox extends Command {
	public const ARGUMENT_MAILBOX_ID = 'mailbox-id';

	public function __construct(
		private readonly MailboxMapper $mailboxMapper,
		private readonly ITimeFactory $timeFactory,
		private readonly HordeSyncTokenParser $syncTokenParser,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('mail:mailbox:info');
		$this->setDescription('Show information about a mailbox');
		$this->addArgument(self::ARGUMENT_MAILBOX_ID, InputArgument::REQUIRED, 'Id of the mailbox');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$mailboxId = (int)$input->getArgument(self::ARGUMENT_MAILBOX_ID);

		try {
			$mailbox = $this->mailboxMapper->findById($mailboxId);
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>Mailbox $mailboxId does not exist</error>");

			return self::FAILURE;
		}

		$now = $this->timeFactory->getTime();

		$table = new Table($output);
		$table->setHeaders(['Property', 'Value']);
		$table->addRows([
			['Id', $mailbox->getId()],
			['Account Id', $mailbox->getAccountId()],
			['Name', $mailbox->getName() ?? '-'],
			['Delimiter', $mailbox->getDelimiter() ?? '-'],
			['Attributes', $mailbox->getAttributes() ?? '-'],
			['Special Use', $mailbox->getSpecialUse() ?? '-'],
			['Selectable', $this->formatNullableBool($mailbox->getSelectable())],
			['Messages', $mailbox->getMessages()],
			['Unseen', $mailbox->getUnseen()],
			['Sync In Background', $this->formatSyncInBackground($mailbox)],
			['Shared', $this->formatNullableBool($mailbox->isShared())],
			['My ACLs', $mailbox->getMyAcls() ?? '-'],
			// @todo remove for stable5.10 backport
			['Remote Id', $mailbox->getRemoteId() ?? '-'],
			// @todo remove for stable5.10 backport
			['Remote Parent Id', $mailbox->getRemoteParentId() ?? '-'],
			// @todo remove for stable5.10 backport
			['State', $mailbox->getState() ?? '-'],
			new TableSeparator(),
			['Token New', $this->formatToken($mailbox->getSyncNewToken())],
			['Token Changed', $this->formatToken($mailbox->getSyncChangedToken())],
			['Token Vanished', $this->formatToken($mailbox->getSyncVanishedToken())],
			new TableSeparator(),
			['Lock New', $this->formatLock($mailbox->getSyncNewLock(), $mailbox->hasNewLock($now))],
			['Lock Changed', $this->formatLock($mailbox->getSyncChangedLock(), $mailbox->hasChangedLock($now))],
			['Lock Vanished', $this->formatLock($mailbox->getSyncVanishedLock(), $mailbox->hasVanishedLock($now))],
		]);
		$table->render();

		return self::SUCCESS;
	}

	private function formatSyncInBackground(Mailbox $mailbox): string {
		if ($mailbox->isInbox()) {
			return 'yes';
		}
		return $this->formatNullableBool($mailbox->getSyncInBackground());
	}

	private function formatNullableBool(?bool $value): string {
		if ($value === null) {
			return '-';
		}
		return $value ? 'yes' : 'no';
	}

	private function formatLock(?int $lock, bool $active): string {
		if ($lock === null) {
			return '-';
		}
		return date('Y-m-d H:i:s', $lock) . ($active ? ' (active)' : ' (stale)');
	}

	private function formatToken(?string $token): string {
		if ($token === null) {
			return '-';
		}
		$parsed = $this->syncTokenParser->parseSyncToken($token);
		return sprintf(
			'uid=%s, validity=%s, modseq=%s',
			$parsed->getNextUid() ?? '-',
			$parsed->getUidValidity() ?? '-',
			$parsed->getHighestModSeq() ?? '-',
		);
	}
}
