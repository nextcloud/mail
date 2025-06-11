<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP\Threading;

use JsonSerializable;
use ReturnTypeWillChange;
use RuntimeException;
use function array_key_exists;
use function spl_object_id;

final class Container implements JsonSerializable {
	/** @var Message|null */
	private $message;

	/** @var string|null */
	private $id;

	/** @var bool */
	private $root;

	/** @var Container|null */
	private $parent;

	/** @var Container[] */
	private $children = [];

	private function __construct(?Message $message,
		?string $id = null,
		bool $root = false) {
		$this->message = $message;
		$this->id = $id;
		$this->root = $root;
	}

	public static function root(): self {
		return new self(
			null,
			null,
			true
		);
	}

	public static function empty(?string $id = null): self {
		return new self(
			null,
			$id,
		);
	}

	public static function with(Message $message): self {
		return new self(
			$message
		);
	}

	public function fill(Message $message): void {
		$this->message = $message;
	}

	public function hasMessage(): bool {
		return $this->message !== null;
	}

	public function getMessage(): ?Message {
		return $this->message;
	}

	public function getId(): ?string {
		return $this->id;
	}

	public function isRoot(): bool {
		return $this->root;
	}

	public function hasParent(): bool {
		return $this->parent !== null;
	}

	public function getParent(): Container {
		if ($this->isRoot() || $this->parent === null) {
			throw new RuntimeException('Container root has no parent');
		}
		return $this->parent;
	}

	public function setParent(?Container $parent): void {
		$this->unlink();
		$this->parent = $parent;
		if ($parent !== null) {
			$parent->children[spl_object_id($this)] = $this;
		}
	}

	public function hasAncestor(Container $container): bool {
		if ($this->parent === $container) {
			return true;
		}
		if ($this->parent !== null) {
			return $this->parent->hasAncestor($container);
		}
		return false;
	}

	public function unlink(): void {
		if ($this->parent !== null) {
			$this->parent->removeChild($this);
		}
		$this->parent = null;
	}

	private function removeChild(Container $child): void {
		$objId = spl_object_id($child);
		if (array_key_exists($objId, $this->children)) {
			unset($this->children[$objId]);
		}
	}

	public function hasChildren(): bool {
		return $this->children !== [];
	}

	/**
	 * @return Container[]
	 */
	public function getChildren(): array {
		return $this->children;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'message' => $this->message,
			'id' => $this->id,
			'root' => $this->root,
			'children' => $this->children,
		];
	}
}
