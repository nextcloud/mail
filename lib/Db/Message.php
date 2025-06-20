<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use Horde_Mail_Rfc822_Identification;
use JsonSerializable;
use OCA\Mail\AddressList;
use OCA\Mail\Service\Avatar\Avatar;
use OCP\AppFramework\Db\Entity;
use ReturnTypeWillChange;
use function in_array;
use function json_decode;
use function json_encode;

/**
 * @method void setUid(int $uid)
 * @method int getUid()
 * @method string|null getMessageId()
 * @method void setReferences(string $references)
 * @method string|null getReferences()
 * @method string|null getInReplyTo()
 * @method string|null getThreadRootId()
 * @method void setMailboxId(int $mailbox)
 * @method int getMailboxId()
 * @method void setSubject(string $subject)
 * @method string getSubject()
 * @method void setSentAt(int $time)
 * @method int getSentAt()
 * @method void setFlagAnswered(bool $answered)
 * @method bool|null getFlagAnswered()
 * @method void setFlagDeleted(bool $deleted)
 * @method bool|null getFlagDeleted()
 * @method void setFlagDraft(bool $answered)
 * @method bool|null|null getFlagDraft()
 * @method void setFlagFlagged(bool $flagged)
 * @method bool|null getFlagFlagged()
 * @method void setFlagSeen(bool $seen)
 * @method bool|null getFlagSeen()
 * @method void setFlagForwarded(bool $forwarded)
 * @method bool|null getFlagForwarded()
 * @method void setFlagJunk(bool $junk)
 * @method bool|null getFlagJunk()
 * @method void setFlagNotjunk(bool $notjunk)
 * @method bool|null getFlagNotjunk()
 * @method void setStructureAnalyzed(bool $analyzed)
 * @method bool|null getStructureAnalyzed()
 * @method void setFlagAttachments(?bool $hasAttachments)
 * @method null|bool getFlagAttachments()
 * @method void setFlagImportant(bool $important)
 * @method bool|null getFlagImportant()
 * @method void setFlagMdnsent(bool $mdnsent)
 * @method bool|null getFlagMdnsent()
 * @method void setPreviewText(?string $subject)
 * @method null|string getPreviewText()
 * @method void setSummary(?string $summary)
 * @method null|string getSummary()
 * @method void setUpdatedAt(int $time)
 * @method int getUpdatedAt()
 * @method bool isImipMessage()
 * @method void setImipMessage(bool $imipMessage)
 * @method bool isImipProcessed()
 * @method void setImipProcessed(bool $imipProcessed)
 * @method bool isImipError()
 * @method void setImipError(bool $imipError)
 * @method bool|null isEncrypted()
 * @method void setEncrypted(bool|null $encrypted)
 * @method bool getMentionsMe()
 * @method void setMentionsMe(bool $isMentionned)
 */
class Message extends Entity implements JsonSerializable {
	private const MUTABLE_FLAGS = [
		'answered',
		'deleted',
		'draft',
		'flagged',
		'seen',
		'forwarded',
		'$junk',
		'$notjunk',
		'mdnsent',
		Tag::LABEL_IMPORTANT,
		'$important' // @todo remove this when we have removed all references on IMAP to $important @link https://github.com/nextcloud/mail/issues/25
	];

	protected $uid;
	protected $messageId;
	protected $references;
	protected $inReplyTo;
	protected $threadRootId;
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
	protected $flagMdnsent;
	protected $previewText;
	protected $summary;
	protected $imipMessage = false;
	protected $imipProcessed = false;
	protected $imipError = false;
	protected $mentionsMe = false;

	/**
	 * @var bool|null
	 */
	protected $encrypted;

	/** @var AddressList */
	private $from;

	/** @var AddressList */
	private $to;

	/** @var AddressList */
	private $cc;

	/** @var AddressList */
	private $bcc;

	/** @var Tag[] */
	private $tags = [];

	/** @var Avatar|null */
	private $avatar;

	/** @var bool */
	private $fetchAvatarFromClient = false;

	public function __construct() {
		$this->from = new AddressList([]);
		$this->to = new AddressList([]);
		$this->cc = new AddressList([]);
		$this->bcc = new AddressList([]);

		$this->addType('uid', 'integer');
		$this->addType('mailboxId', 'integer');
		$this->addType('sentAt', 'integer');
		$this->addType('flagAnswered', 'boolean');
		$this->addType('flagDeleted', 'boolean');
		$this->addType('flagDraft', 'boolean');
		$this->addType('flagFlagged', 'boolean');
		$this->addType('flagSeen', 'boolean');
		$this->addType('flagForwarded', 'boolean');
		$this->addType('flagJunk', 'boolean');
		$this->addType('flagNotjunk', 'boolean');
		$this->addType('structureAnalyzed', 'boolean');
		$this->addType('flagAttachments', 'boolean');
		$this->addType('flagImportant', 'boolean');
		$this->addType('flagMdnsent', 'boolean');
		$this->addType('updatedAt', 'integer');
		$this->addType('imipMessage', 'boolean');
		$this->addType('imipProcessed', 'boolean');
		$this->addType('imipError', 'boolean');
		$this->addType('encrypted', 'boolean');
	}

	/**
	 * @param string|null $messageId
	 *
	 * Parses the message ID to see if it is a valid Horde_Mail_Rfc822_Identification
	 * before setting it, or sets null if it is not valid.
	 */
	public function setMessageId(?string $messageId): void {
		$this->setMessageIdFieldIfNotEmpty('messageId', $messageId);
	}

	public function setRawReferences(?string $references): void {
		$parsed = new Horde_Mail_Rfc822_Identification($references);
		$this->setter('references', [json_encode($parsed->ids)]);
	}

	public function setInReplyTo(?string $inReplyTo): void {
		$this->setMessageIdFieldIfNotEmpty('inReplyTo', $inReplyTo);
	}

	public function setThreadRootId(?string $messageId): void {
		$threadRootId = (!empty($messageId)) ? '<' . rtrim(ltrim($messageId, '<'), '>') . '>' : $this->getMessageId();
		$parsed = new Horde_Mail_Rfc822_Identification($threadRootId);
		$this->setter('threadRootId', [$parsed->ids[0] ?? $this->getMessageId()]);
	}

	private function setMessageIdFieldIfNotEmpty(string $field, ?string $id): void {
		$id = (!empty($id)) ? '<' . rtrim(ltrim($id, '<'), '>') . '>' : null;
		$parsed = new Horde_Mail_Rfc822_Identification($id);
		$this->setter($field, [$parsed->ids[0] ?? null]);
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
	 * @return Tag[]
	 */
	public function getTags(): array {
		return $this->tags;
	}

	/**
	 * @param array $tags
	 */
	public function setTags(array $tags): void {
		$this->tags = $tags;
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

	/**
	 * @return void
	 */
	public function setFlag(string $flag, bool $value = true) {
		if (!in_array($flag, self::MUTABLE_FLAGS, true)) {
			// Ignore
			return;
		}
		if ($flag === Tag::LABEL_IMPORTANT) {
			$this->setFlagImportant($value);
		} elseif ($flag === '$junk') {
			$this->setFlagJunk($value);
		} elseif ($flag === '$notjunk') {
			$this->setFlagNotjunk($value);
		} else {
			$this->setter(
				$this->columnToProperty("flag_$flag"),
				[$value]
			);
		}
	}
	/**
	 * @param Avatar|null $avatar
	 * @return void
	 */
	public function setAvatar(?Avatar $avatar): void {
		$this->avatar = $avatar;
	}

	public function setFetchAvatarFromClient(bool $fetchAvatarFromClient): void {
		$this->fetchAvatarFromClient = $fetchAvatarFromClient;
	}

	/**
	 * @return ?Avatar
	 */
	public function getAvatar(): ?Avatar {
		return $this->avatar;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		$tags = $this->getTags();
		$indexed = array_combine(
			array_map(
				static function (Tag $tag) {
					return $tag->getImapLabel();
				}, $tags),
			$tags
		);

		return [
			'databaseId' => $this->getId(),
			'uid' => $this->getUid(),
			'subject' => $this->getSubject(),
			'dateInt' => $this->getSentAt(),
			'flags' => [
				'seen' => ($this->getFlagSeen() === true),
				'flagged' => ($this->getFlagFlagged() === true),
				'answered' => ($this->getFlagAnswered() === true),
				'deleted' => ($this->getFlagDeleted() === true),
				'draft' => ($this->getFlagDraft() === true),
				'forwarded' => ($this->getFlagForwarded() === true),
				'hasAttachments' => ($this->getFlagAttachments() ?? false),
				'important' => ($this->getFlagImportant() === true),
				'$junk' => ($this->getFlagJunk() === true),
				'$notjunk' => ($this->getFlagNotjunk() === true),
				'mdnsent' => ($this->getFlagMdnsent() === true),
			],
			'tags' => $indexed,
			'from' => $this->getFrom()->jsonSerialize(),
			'to' => $this->getTo()->jsonSerialize(),
			'cc' => $this->getCc()->jsonSerialize(),
			'bcc' => $this->getBcc()->jsonSerialize(),
			'mailboxId' => $this->getMailboxId(),
			'messageId' => $this->getMessageId(),
			'inReplyTo' => $this->getInReplyTo(),
			'references' => empty($this->getReferences()) ? null: json_decode($this->getReferences(), true),
			'threadRootId' => $this->getThreadRootId(),
			'imipMessage' => $this->isImipMessage(),
			'previewText' => $this->getPreviewText(),
			'summary' => $this->getSummary(),
			'encrypted' => ($this->isEncrypted() === true),
			'mentionsMe' => $this->getMentionsMe(),
			'avatar' => $this->avatar?->jsonSerialize(),
			'fetchAvatarFromClient' => $this->fetchAvatarFromClient,
		];
	}
}
