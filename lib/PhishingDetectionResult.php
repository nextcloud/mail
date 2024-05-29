<?php

declare(strict_types=1);

/**
 * @copyright 2024 Hamza Mahjoubi <hamza.mahjoubi221@proton.me>
 *
 * @author 2024 Hamza Mahjoubi <hamza.mahjoubi221@proton.me>
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
 *
 */

namespace OCA\Mail;

use JsonSerializable;
use ReturnTypeWillChange;

/**
 * @psalm-immutable
 */
class PhishingDetectionResult implements JsonSerializable {

    public const DATE_CHECK = "Date";
    public const LINK_CHECK = "Link";
    public const REPLYTO_CHECK = "Reply-To";
    public const CUSTOM_EMAIL_CHECK = "Custom Email";
    public const CONTACTS_CHECK = "Contacts";
    public const TRUSTED_CHECK = "Trusted";

    private string $message = "";
    private bool $isPhishing;
    private array $additionalData = [];
    private $type;

    public function __construct(string $type, bool $isPhishing, string $message = "",  array $additionalData = []) {
        $this->type = $type;
        $this->message = $message;
        $this->isPhishing = $isPhishing;
        $this->additionalData = $additionalData;

    }

    public function getType(): string {
        return $this->type;
    }
    public function isPhishing(): bool {
        return $this->isPhishing;
    }

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'type' => $this->type,
            'isPhishing' => $this->isPhishing,
			'message' => $this->message,
            'additionalData' => $this->additionalData,
		];
	}

}
