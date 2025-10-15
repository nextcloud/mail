<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Model\IMAPMessage;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;
use function method_exists;
use function sprintf;

class AddMissingMessageIds implements IRepairStep {
	/** @var MessageMapper */
	private $mapper;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(MessageMapper $mapper,
		LoggerInterface $logger) {
		$this->mapper = $mapper;
		$this->logger = $logger;
	}

	#[\Override]
	public function getName() {
		return 'Add a generated message-id to all Mail messages that have none';
	}

	#[\Override]
	public function run(IOutput $output): void {
		$output->info('Looking up messages without a message-id');

		/**
		 * During the upgrade to v1.9.2/v1.10.0 the old version of the
		 * mapper is loaded before this new repair step is performed. Hence even after
		 * the program code got replaced, the class doesn't have the new method. We
		 *
		 * Do the ugly method check here and hope that the repair is run soonish.
		 *
		 * @see https://github.com/nextcloud/mail/issues/4746
		 */
		if (!method_exists($this->mapper, 'findWithEmptyMessageId')) {
			$output->warning('New Mail code hasn\'t been loaded yes, skipping message clean-up. Please run `occ maintenance:repair` after the upgrade.');
			return;
		}

		$toFix = $this->mapper->findWithEmptyMessageId();
		if ($toFix === []) {
			return;
		}
		$output->info(sprintf('%d messages need an update', count($toFix)));
		$output->startProgress(count($toFix));
		foreach ($toFix as $message) {
			$id = IMAPMessage::generateMessageId();
			$message->setMessageId($id);

			// The thread root is is null if the message wasn't matched to a thread
			// based on its subject. In that case we set default for the thread root
			// as well.
			if ($message->getThreadRootId() === null) {
				$message->setThreadRootId($id);
			}

			$this->mapper->update($message);
			$output->advance();
		}
	}
}
