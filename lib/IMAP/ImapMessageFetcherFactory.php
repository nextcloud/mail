<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\IMAP;

use Horde_Imap_Client_Base;
use OCA\Mail\IMAP\Charset\Converter;
use OCA\Mail\Service\Html;
use OCA\Mail\Service\SmimeService;

class ImapMessageFetcherFactory {
	private Html $htmlService;
	private SmimeService $smimeService;
	private Converter $charsetConverter;

	public function __construct(Html         $htmlService,
		SmimeService $smimeService,
		Converter $charsetConverter) {
		$this->htmlService = $htmlService;
		$this->smimeService = $smimeService;
		$this->charsetConverter = $charsetConverter;
	}

	public function build(int $uid,
		string $mailbox,
		Horde_Imap_Client_Base $client,
		string $userId): ImapMessageFetcher {
		return new ImapMessageFetcher(
			$uid,
			$mailbox,
			$client,
			$userId,
			$this->htmlService,
			$this->smimeService,
			$this->charsetConverter,
		);
	}
}
