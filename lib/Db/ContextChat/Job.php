<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db\ContextChat;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method void setMailboxId(int $mailboxId)
 * @method int getMailboxId()
 * @method void setLastMessageId(int $nextMessageId)
 * @method int getLastMessageId()
 */
class Job extends Entity implements JsonSerializable {
	/** @var int */
	protected $mailboxId;

	/** @var int */
	protected $nextMessageId;

	public function __construct() {
		$this->addType('mailboxId', 'integer');
		$this->addType('lastMessageId', 'integer');
	}

	#[\Override]
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'mailboxId' => $this->getMailboxId(),
			'lastMessageId' => $this->getLastMessageId(),
		];
	}
}
