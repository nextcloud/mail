<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Events;

use Horde_Mime_Mail;
use OCA\Mail\Account;
use OCA\Mail\Model\IMessage;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCP\EventDispatcher\Event;

class MessageSentEvent extends Event {

	/** @var Account */
	private $account;

	/** @var NewMessageData */
	private $newMessageData;

	/** @var null|RepliedMessageData */
	private $repliedMessageData;

	/** @var int|null */
	private $draftUid;

	/** @var IMessage */
	private $message;

	/** @var Horde_Mime_Mail */
	private $mail;

	public function __construct(Account $account,
								NewMessageData $newMessageData,
								?RepliedMessageData $repliedMessageData,
								?int $draftUid,
								IMessage $message,
								Horde_Mime_Mail $mail) {
		parent::__construct();
		$this->account = $account;
		$this->newMessageData = $newMessageData;
		$this->repliedMessageData = $repliedMessageData;
		$this->draftUid = $draftUid;
		$this->message = $message;
		$this->mail = $mail;
	}

	public function getAccount(): Account {
		return $this->account;
	}

	public function getNewMessageData(): NewMessageData {
		return $this->newMessageData;
	}

	public function getRepliedMessageData(): ?RepliedMessageData {
		return $this->repliedMessageData;
	}

	public function getDraftUid(): ?int {
		return $this->draftUid;
	}

	public function getMessage(): IMessage {
		return $this->message;
	}

	public function getMail(): Horde_Mime_Mail {
		return $this->mail;
	}
}
