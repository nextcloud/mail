<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IMemcache;
use function floor;

/**
 * "Decorator" around Horde's IMAP client to add auth error rate limiting.
 *
 * This is not a real decorator because the component to decorate doesn't have
 * an interface, making it hard to base a decorator on composition.
 * For simplicity the component is decorated by inheritance.
 */
class HordeImapClient extends Horde_Imap_Client_Socket {
	private ?IMemcache $rateLimiterCache = null;
	private ?ITimeFactory $timeFactory = null;
	private ?string $hash = null;

	public function enableRateLimiter(
		IMemcache $cache,
		string $hash,
		ITimeFactory $timeFactory,
	): void {
		$this->rateLimiterCache = $cache;
		$this->timeFactory = $timeFactory;
		$this->hash = $hash;
	}

	#[\Override]
	protected function _login() {
		if ($this->rateLimiterCache === null) {
			return parent::_login();
		}

		$now = $this->timeFactory->getTime();
		$window = floor($now / (3 * 60 * 60));
		$cacheKey = $this->hash . $window;

		$counter = $this->rateLimiterCache->get($cacheKey);
		if ($counter !== null && $counter >= 3) {
			// Enough errors. Let's fail without involving IMAP
			throw new Horde_Imap_Client_Exception(
				'Too many auth attempts',
				Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED
			);
		}

		try {
			return parent::_login();
		} catch (Horde_Imap_Client_Exception $e) {
			if ($e->getCode() === Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED
				&& $e->getMessage() === 'Authentication failed.') {
				$this->rateLimiterCache->inc($cacheKey);
			}
			throw $e;
		}
	}
}
