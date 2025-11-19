<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

use Horde_Imap_Client_Base;
use OCA\Mail\IMAP\Charset\Converter;
use OCA\Mail\Service\Html;
use OCA\Mail\Service\PhishingDetection\PhishingDetectionService;
use OCA\Mail\Service\SmimeService;

class ImapMessageFetcherFactory {
	public function __construct(
		private readonly Html $htmlService,
		private readonly SmimeService $smimeService,
		private readonly Converter $charsetConverter,
		private readonly PhishingDetectionService $phishingDetectionService
	) {
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
			$this->phishingDetectionService,
		);
	}
}
