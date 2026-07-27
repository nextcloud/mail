<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Exception;

use Horde_Imap_Client_Exception;
use OCP\AppFramework\Http;
use Throwable;

/**
 * IMAP rejected the account's credentials (or the local auth rate limiter is
 * engaged). Unlike a generic ServiceException this is sent to the client as a
 * distinct, machine-readable failure so the frontend can pause syncing and
 * ask the user to update their credentials instead of retrying forever.
 */
class ImapAuthenticationFailedException extends ClientException {
	public const REASON_AUTHENTICATION_FAILED = 'AUTHENTICATION_FAILED';
	public const REASON_RATE_LIMITED = 'RATE_LIMITED';

	private string $reason;

	public function __construct(string $message = 'IMAP authentication failed',
		int $code = 0,
		?Throwable $previous = null,
		string $reason = self::REASON_AUTHENTICATION_FAILED) {
		parent::__construct($message, $code, $previous);
		$this->reason = $reason;
	}

	public static function fromHorde(Horde_Imap_Client_Exception $e): self {
		$reason = $e->getMessage() === 'Too many auth attempts'
			? self::REASON_RATE_LIMITED
			: self::REASON_AUTHENTICATION_FAILED;
		return new self('IMAP authentication failed', $e->getCode(), $e, $reason);
	}

	/**
	 * Find an IMAP authentication failure anywhere in an exception chain
	 */
	public static function findHordeAuthFailure(?Throwable $e): ?Horde_Imap_Client_Exception {
		while ($e !== null) {
			if ($e instanceof Horde_Imap_Client_Exception
				&& $e->getCode() === Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED) {
				return $e;
			}
			$e = $e->getPrevious();
		}
		return null;
	}

	public function getReason(): string {
		return $this->reason;
	}

	#[\Override]
	public function getHttpCode(): int {
		return Http::STATUS_FAILED_DEPENDENCY;
	}
}
