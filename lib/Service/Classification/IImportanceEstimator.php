<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Service\Classification;

use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;

interface IImportanceEstimator {

	public const RANGE_MIN = 1;
	public const RANGE_MAX = 9;

	/**
	 * Estimate how important the given message is for the user's inbox
	 *
	 * This method returns a number between RANGE_MIN and RANGE_MAX for how important
	 * this message is for a certain aspect. If the implementation can not estimate
	 * the importance – e.g. due to lack of available data – it can return `null`.
	 *
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param Message $message
	 *
	 * @return null|int a value in [RANGE_MIN, RANGE_MAX] or null for neutral
	 */
	public function estimateImportance(Account $account, Mailbox $mailbox, Message $message): ?int;

}
