<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\PhishingDetection;

use Horde_Imap_Client;
use OCA\Mail\PhishingDetectionResult;
use OCP\IL10N;

class ImapFlagCheck {
	protected IL10N $l10n;

	public function __construct(IL10N $l10n) {
		$this->l10n = $l10n;
	}

	/**
	 * @param string[] $messageFlags
	 */
	public function run(array $messageFlags): PhishingDetectionResult {
		$flaggedAsSpam = in_array(Horde_Imap_Client::FLAG_JUNK, $messageFlags, true) || in_array('junk', $messageFlags, true);
		// TODO: Use Horde const once the flag is implemented there
		//  (https://github.com/bytestream/Imap_Client/blob/master/lib/Horde/Imap/Client.php#L153).
		$flaggedAsPhishing = in_array('$phishing', $messageFlags, true);

		if ($flaggedAsSpam && $flaggedAsPhishing) {
			return new PhishingDetectionResult(PhishingDetectionResult::IMAP_FLAG_CHECK, true, $this->l10n->t('Mail server marked this message as phishing attempt'));
		}

		return new PhishingDetectionResult(PhishingDetectionResult::IMAP_FLAG_CHECK, false);
	}
}
