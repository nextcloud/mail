<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 owncloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail;

use Horde_Mime_Part;

class Attachment {
	private ?string $id;
	private ?string $name;
	private string $type;
	private string $content;
	private int $size;

	public function __construct(
		?string $id,
		?string $name,
		string $type,
		string $content,
		int $size,
	) {
		$this->id = $id;
		$this->name = $name;
		$this->type = $type;
		$this->content = $content;
		$this->size = $size;
	}

	public static function fromMimePart(Horde_Mime_Part $mimePart): self {
		return new Attachment(
			$mimePart->getMimeId(),
			$mimePart->getName(),
			$mimePart->getType(),
			$mimePart->getContents(),
			(int)$mimePart->getBytes(),
		);
	}

	public function getId(): ?string {
		return $this->id;
	}

	public function getName(): ?string {
		return $this->name;
	}

	public function getType(): string {
		return $this->type;
	}

	public function getContent(): string {
		return $this->content;
	}

	public function getSize(): int {
		return $this->size;
	}
}
