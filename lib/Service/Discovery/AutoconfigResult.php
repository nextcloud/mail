<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Discovery;

final class AutoconfigResult {
	public function __construct(
		private ?ImapServerConfiguration $imap,
		private ?JmapServerConfiguration $jmap,
	) {
	}

	public function getImap(): ?ImapServerConfiguration {
		return $this->imap;
	}

	public function getJmap(): ?JmapServerConfiguration {
		return $this->jmap;
	}
}
