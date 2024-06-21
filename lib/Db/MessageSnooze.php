<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setMailboxId(int $mailboxId)
 * @method int getMailboxId()
 * @method void setUid(int $uid)
 * @method int getUid()
 * @method void setSnoozedUntil(int $snoozeUntil)
 * @method int getSnoozedUntil()
 * @method void setSrcMailboxId(int $srcMailboxId)
 * @method int getSrcMailboxId()
 */
class MessageSnooze extends Entity {

	/** @var int */
	protected $mailboxId;

	/** @var int */
	protected $uid;

	/** @var int */
	protected $snoozedUntil;

	/** @var int */
	protected $srcMailboxId;

	public function __construct() {
		$this->addType('mailboxId', 'integer');
		$this->addType('uid', 'integer');
		$this->addType('snoozedUntil', 'integer');
		$this->addType('srcMailboxId', 'integer');
	}
}
