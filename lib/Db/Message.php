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

namespace OCA\Mail\Db;

use JsonSerializable;
use OCA\Mail\AddressList;
use OCP\AppFramework\Db\Entity;
use function in_array;

/**
 * @method void setUid(int $uid)
 * @method int getUid()
 * @method void setMessageId(string $id)
 * @method string getMessageId()
 * @method void setMailboxId(int $mailbox)
 * @method int getMailboxId()
 * @method void setSubject(string $subject)
 * @method string getSubject()
 * @method void setSentAt(int $time)
 * @method int getSentAt()
 * @method void setFlagAnswered(bool $answered)
 * @method bool getFlagAnswered()
 * @method void setFlagDeleted(bool $deleted)
 * @method bool getFlagDeleted()
 * @method void setFlagDraft(bool $answered)
 * @method bool getFlagDraft()
 * @method void setFlagFlagged(bool $flagged)
 * @method bool getFlagFlagged()
 * @method void setFlagSeen(bool $seen)
 * @method bool getFlagSeen()
 * @method void setFlagForwarded(bool $forwarded)
 * @method bool getFlagForwarded()
 * @method void setFlagJunk(bool $junk)
 * @method bool getFlagJunk()
 * @method void setFlagNotjunk(bool $notjunk)
 * @method bool getFlagNotjunk()
 * @method void setStructureAnalyzed(bool $analyzed)
 * @method bool getStructureAnalyzed()
 * @method void setFlagAttachments(?bool $hasAttachments)
 * @method null|bool getFlagAttachments()
 * @method void setFlagImportant(bool $important)
 * @method bool getFlagImportant()
 * @method void setPreviewText(?string $subject)
 * @method null|string getPreviewText()
 * @method void setUpdatedAt(int $time)
 * @method int getUpdatedAt()
 */
class Message extends Entity implements JsonSerializable {
	private const MUTABLE_FLAGS = [
		'answered',
		'deleted',
		'draft',
		'flagged',
		'seen',
		'forwarded',
		'junk',
		'notjunk',
		'important',
	];

	protected $uid;
	protected $messageId;
	protected $mailboxId;
	protected $subject;
	protected $sentAt;
	protected $flagAnswered;
	protected $flagDeleted;
	protected $flagDraft;
	protected $flagFlagged;
	protected $flagSeen;
	protected $flagForwarded;
	protected $flagJunk;
	protected $flagNotjunk;
	protected $updatedAt;
	protected $structureAnalyzed;
	protected $flagAttachments;
	protected $flagImportant = false;
	protected $previewText;

	/** @var AddressList */
	private $from;

	/** @var AddressList */
	private $to;

	/** @var AddressList */
	private $cc;

	/** @var AddressList */
	private $bcc;

	public function __construct() {
		$this->from = new AddressList([]);
		$this->to = new AddressList([]);
		$this->cc = new AddressList([]);
		$this->bcc = new AddressList([]);

		$this->addType('uid', 'integer');
		$this->addType('sentAt', 'integer');
		$this->addType('flagAnswered', 'bool');
		$this->addType('flagDeleted', 'bool');
		$this->addType('flagDraft', 'bool');
		$this->addType('flagFlagged', 'bool');
		$this->addType('flagSeen', 'bool');
		$this->addType('flagForwarded', 'bool');
		$this->addType('flagJunk', 'bool');
		$this->addType('flagNotjunk', 'bool');
		$this->addType('structureAnalyzed', 'bool');
		$this->addType('flagAttachments', 'bool');
		$this->addType('flagImportant', 'bool');
		$this->addType('updatedAt', 'integer');
	}

	/**
	 * @return AddressList
	 */
	public function getFrom(): AddressList {
		return $this->from;
	}

	/**
	 * @param AddressList $from
	 */
	public function setFrom(AddressList $from): void {
		$this->from = $from;
	}

	/**
	 * @return AddressList
	 */
	public function getTo(): AddressList {
		return $this->to;
	}

	/**
	 * @param AddressList $to
	 */
	public function setTo(AddressList $to): void {
		$this->to = $to;
	}

	/**
	 * @return AddressList
	 */
	public function getCc(): AddressList {
		return $this->cc;
	}

	/**
	 * @param AddressList $cc
	 */
	public function setCc(AddressList $cc): void {
		$this->cc = $cc;
	}

	/**
	 * @return AddressList
	 */
	public function getBcc(): AddressList {
		return $this->bcc;
	}

	/**
	 * @param AddressList $bcc
	 */
	public function setBcc(AddressList $bcc): void {
		$this->bcc = $bcc;
	}

	public function setFlag(string $flag, bool $value = true) {
		if (!in_array($flag, self::MUTABLE_FLAGS, true)) {
			// Ignore
			return;
		}

		$this->setter(
			$this->columnToProperty("flag_$flag"),
			[$value]
		);
	}

	public function jsonSerialize() {
		return [
			'id' => $this->getUid(), // Change to UID on front-end
			'subject' => $this->getSubject(),
			'dateInt' => $this->getSentAt(),
			'flags' => [
				'seen' => $this->getFlagSeen(),
				'flagged' => $this->getFlagFlagged(),
				'answered' => $this->getFlagAnswered(),
				'deleted' => $this->getFlagDeleted(),
				'draft' => $this->getFlagDraft(),
				'forwarded' => $this->getFlagForwarded(),
				'hasAttachments' => $this->getFlagAttachments() ?? false,
				'important' => $this->getFlagImportant(),
				'junk' => $this->getFlagJunk(),
			],
			'from' => $this->getFrom()->jsonSerialize(),
			'to' => $this->getTo()->jsonSerialize(),
			'cc' => $this->getCc()->jsonSerialize(),
			'bcc' => $this->getBcc()->jsonSerialize(),
		];
	}
}
