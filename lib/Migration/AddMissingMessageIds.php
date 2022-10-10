<?php

declare(strict_types=1);

/**
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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

	public function getName() {
		return 'Add a generated message-id to all Mail messages that have none';
	}

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
		if (empty($toFix)) {
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
