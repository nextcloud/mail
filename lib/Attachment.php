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
	private Horde_Mime_Part $mimePart;

	public function __construct(Horde_Mime_Part $mimePart) {
		$this->mimePart = $mimePart;
	}

	public function getContents(): string {
		return $this->mimePart->getContents();
	}

	public function getName(): ?string {
		return $this->mimePart->getName();
	}

	public function getType(): string {
		return $this->mimePart->getType();
	}
}
