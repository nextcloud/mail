<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 owncloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail;

use Horde_Mime_Part;

class Attachment {
	public function __construct(
		private readonly ?string $id,
		private readonly ?string $name,
		private readonly string $type,
		private readonly string $content,
		private readonly int $size
	) {
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
