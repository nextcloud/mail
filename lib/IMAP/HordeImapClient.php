<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Exception_NoSupportExtension;
use Horde_Imap_Client_Socket;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IMemcache;
use OCP\IMemcacheTTL;

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

	public function __construct(
		array $params,
		private IMAPClientFactory $factory,
	) {
		parent::__construct($params);
	}

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
	public function login() {
		$initiallyAutheticated = $this->_isAuthenticated;
		parent::login();

		if ($initiallyAutheticated === false && $this->capability->query('ID')) {
			try {
				$this->sendID();
				/* ID is queued - force sending the queued command. */
				$this->_sendCmd($this->_pipeline());
			} catch (Horde_Imap_Client_Exception_NoSupportExtension) {
				// Ignore if server doesn't support ID extension.
			}
		}
	}

	/** Failed attempts are forgotten after this many seconds */
	private const FAILURE_TTL = 3 * 60 * 60;
	/** Unthrottled attempts before the leaky bucket engages */
	private const THROTTLE_THRESHOLD = 3;
	/** Throttled attempts allowed at the fast interval before slowing down */
	private const FAST_RETRIES = 3;
	private const FAST_RETRY_INTERVAL = 30;
	private const SLOW_RETRY_INTERVAL = 5 * 60;

	protected function imapLogin() {
		$result = parent::_login();
		$this->factory->recordLogin($this->_params['hostspec']);
		return $result;
	}

	#[\Override]
	protected function _login() {
		if ($this->rateLimiterCache === null) {
			return $this->imapLogin();
		}

		$failureKey = $this->hash . '_failures';
		$attemptKey = $this->hash . '_last_attempt';

		$failures = (int)$this->rateLimiterCache->get($failureKey);
		if ($failures >= self::THROTTLE_THRESHOLD) {
			// Leaky bucket instead of a hard block: keep allowing one real
			// attempt per interval so transient server-side failures heal on
			// their own — a few quick retries first, then a slow trickle.
			$interval = $failures < self::THROTTLE_THRESHOLD + self::FAST_RETRIES
				? self::FAST_RETRY_INTERVAL
				: self::SLOW_RETRY_INTERVAL;
			$now = $this->timeFactory->getTime();
			$lastAttempt = (int)$this->rateLimiterCache->get($attemptKey);
			if ($now - $lastAttempt < $interval) {
				// Enough errors. Let's fail without involving IMAP
				throw new Horde_Imap_Client_Exception(
					'Too many auth attempts',
					Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED
				);
			}
			// Not atomic: a parallel burst may pass together, but the next
			// interval only opens once the timestamp is stored
			$this->rateLimiterCache->set($attemptKey, $now, self::FAILURE_TTL);
		}

		try {
			$result = $this->imapLogin();
			if ($failures > 0) {
				// The credentials work (again): reset the throttle state
				$this->rateLimiterCache->remove($failureKey);
				$this->rateLimiterCache->remove($attemptKey);
			}
			return $result;
		} catch (Horde_Imap_Client_Exception $e) {
			if ($e->getCode() === Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED
				&& in_array($e->getMessage(), ['Authentication failed.', 'Mail server denied authentication.'], true)) {
				$this->rateLimiterCache->inc($failureKey);
				if ($this->rateLimiterCache instanceof IMemcacheTTL) {
					$this->rateLimiterCache->setTTL($failureKey, self::FAILURE_TTL);
				}
			}
			throw $e;
		}
	}
}
