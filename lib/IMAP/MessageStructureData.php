<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author 2023 Richard Steinmetz <richard@steinmetz.cloud>
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

namespace OCA\Mail\IMAP;

class MessageStructureData {
	/** @var bool */
	private $hasAttachments;

	/** @var string */
	private $previewText;

	/** @var bool */
	private $isImipMessage;

	private bool $isEncrypted;

	public function __construct(bool $hasAttachments,
								string $previewText,
								bool $isImipMessage,
								bool $isEncrypted) {
		$this->hasAttachments = $hasAttachments;
		$this->previewText = $previewText;
		$this->isImipMessage = $isImipMessage;
		$this->isEncrypted = $isEncrypted;
	}

	public function hasAttachments(): bool {
		return $this->hasAttachments;
	}

	public function getPreviewText(): string {
		return $this->previewText;
	}

	public function isImipMessage(): bool {
		return $this->isImipMessage;
	}

	public function isEncrypted(): bool {
		return $this->isEncrypted;
	}
}
