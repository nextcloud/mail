<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Christoph Wurst <wurst.christoph@gmail.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
