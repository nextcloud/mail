<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Discovery;

use JsonSerializable;
use Override;

/**
 * An IMAP server with the SMTP server for sending
 */
final class ImapServerConfiguration implements JsonSerializable {
	/**
	 * @param ServerEndpoint $incoming IMAP server
	 * @param ServerEndpoint|null $outgoing SMTP server, null if unknown
	 */
	public function __construct(
		private ServerEndpoint $incoming,
		private ?ServerEndpoint $outgoing,
	) {
	}

	public function getIncoming(): ServerEndpoint {
		return $this->incoming;
	}

	public function getOutgoing(): ?ServerEndpoint {
		return $this->outgoing;
	}

	/**
	 * @return array{incoming: ServerEndpoint, outgoing: ServerEndpoint|null}
	 */
	#[Override]
	public function jsonSerialize(): array {
		return [
			'incoming' => $this->incoming,
			'outgoing' => $this->outgoing,
		];
	}
}
