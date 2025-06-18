<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use JsonSerializable;
use OCA\Mail\IMAP\MailboxStats;
use OCP\AppFramework\Db\Entity;
use ReturnTypeWillChange;
use function base64_encode;
use function in_array;
use function json_decode;
use function ltrim;
use function strtolower;

/**
 * @method string getName()
 * @method void setName(string $name)
 * @method int getAccountId()
 * @method void setAccountId(int $accountId)
 * @method string|null getSyncNewToken()
 * @method void setSyncNewToken(string|null $syncNewToken)
 * @method string|null getSyncChangedToken()
 * @method void setSyncChangedToken(string|null $syncNewToken)
 * @method string|null getSyncVanishedToken()
 * @method void setSyncVanishedToken(string|null $syncNewToken)
 * @method int|null getSyncNewLock()
 * @method void setSyncNewLock(int|null $ts)
 * @method int|null getSyncChangedLock()
 * @method void setSyncChangedLock(int|null $ts)
 * @method int|null getSyncVanishedLock()
 * @method void setSyncVanishedLock(int|null $ts)
 * @method string getAttributes()
 * @method void setAttributes(string $attributes)
 * @method string|null getDelimiter()
 * @method void setDelimiter(string|null $delimiter)
 * @method int getMessages()
 * @method void setMessages(int $messages)
 * @method int getUnseen()
 * @method void setUnseen(int $unseen)
 * @method bool|null getSelectable()
 * @method void setSelectable(bool $selectable)
 * @method string getSpecialUse()
 * @method void setSpecialUse(string $specialUse)
 * @method bool|null getSyncInBackground()
 * @method void setSyncInBackground(bool $sync)
 * @method string|null getMyAcls()
 * @method void setMyAcls(string|null $acls)
 * @method bool|null isShared()
 * @method void setShared(bool $shared)
 * @method string getNameHash()
 * @method void setNameHash(string $nameHash)
 */
class Mailbox extends Entity implements JsonSerializable {
	protected $name;
	protected $accountId;
	protected $syncNewToken;
	protected $syncChangedToken;
	protected $syncVanishedToken;
	protected $syncNewLock;
	protected $syncChangedLock;
	protected $syncVanishedLock;
	protected $attributes;
	protected $delimiter;
	protected $messages;
	protected $unseen;
	protected $selectable;
	protected $specialUse;
	protected $syncInBackground;
	protected $myAcls;
	protected $shared;
	protected $nameHash;

	/**
	 * @var int
	 *          Lock timeout for sync (5 minutes)
	 */
	public const LOCK_TIMEOUT = 300;

	public function __construct() {
		$this->addType('accountId', 'integer');
		$this->addType('messages', 'integer');
		$this->addType('unseen', 'integer');
		$this->addType('syncNewLock', 'integer');
		$this->addType('syncChangedLock', 'integer');
		$this->addType('syncVanishedLock', 'integer');
		$this->addType('selectable', 'boolean');
		$this->addType('syncInBackground', 'boolean');
		$this->addType('shared', 'boolean');
	}

	public function isInbox(): bool {
		// https://tools.ietf.org/html/rfc3501#section-5.1
		return strtolower($this->getName()) === 'inbox';
	}

	private function getSpecialUseParsed(): array {
		return json_decode($this->getSpecialUse() ?? '[]', true) ?? [];
	}

	public function isSpecialUse(string $specialUse): bool {
		return in_array(
			ltrim(
				strtolower($specialUse),
				'\\'
			),
			array_map('strtolower', $this->getSpecialUseParsed()),
			true
		);
	}

	public function isCached(): bool {
		return $this->getSyncNewToken() !== null
			&& $this->getSyncChangedToken() !== null
			&& $this->getSyncVanishedToken() !== null;
	}

	public function hasLocks(int $now): bool {
		if ($this->getSyncNewLock() !== null || $this->getSyncNewLock() > ($now - self::LOCK_TIMEOUT)) {
			return true;
		}
		if ($this->getSyncChangedLock() !== null || $this->getSyncChangedLock() > ($now - self::LOCK_TIMEOUT)) {
			return true;
		}
		if ($this->getSyncVanishedLock() !== null || $this->getSyncVanishedLock() > ($now - self::LOCK_TIMEOUT)) {
			return true;
		}
		return false;
	}

	/**
	 * @return MailboxStats
	 */
	public function getStats(): MailboxStats {
		return new MailboxStats($this->getMessages(), $this->getUnseen());
	}

	public function getCacheBuster(): string {
		return hash('md5', implode('|', [
			(string)$this->getId(),
			$this->getSyncNewToken() ?? 'null',
			$this->getSyncChangedToken() ?? 'null',
			$this->getSyncVanishedToken() ?? 'null',
		]));
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		$specialUse = $this->getSpecialUseParsed();
		return [
			'databaseId' => $this->getId(),
			'id' => base64_encode($this->getName()),
			'name' => $this->getName(),
			'accountId' => $this->accountId,
			'displayName' => $this->getName(),
			'attributes' => json_decode($this->attributes ?? '[]', true) ?? [],
			'delimiter' => $this->delimiter,
			'specialUse' => $specialUse,
			'specialRole' => $specialUse[0] ?? 0,
			'mailboxes' => [],
			'syncInBackground' => ($this->getSyncInBackground() === true),
			'unread' => $this->unseen,
			'myAcls' => $this->myAcls,
			'shared' => $this->shared === true,
			'cacheBuster' => $this->getCacheBuster(),
		];
	}
}
