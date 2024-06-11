<?php

declare(strict_types=1);

/*
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author 2024 Richard Steinmetz <richard@steinmetz.cloud>
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
 */

namespace OCA\Mail\IMAP;

use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use OCA\Mail\Cache\Cache;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IMemcache;
use function floor;

/**
 * "Decorator" around Horde's IMAP client to add auth error rate limiting and save the cache on
 * logout.
 *
 * This is not a real decorator because the component to decorate doesn't have
 * an interface, making it hard to base a decorator on composition.
 * For simplicity the component is decorated by inheritance.
 */
class HordeImapClient extends Horde_Imap_Client_Socket {
	private ?IMemcache $rateLimiterCache = null;
	private ?ITimeFactory $timeFactory = null;
	private ?string $hash = null;
	private ?Cache $cacheBackend = null;

	public function __construct(array $params) {
		if (isset($params['cache']['backend']) && $params['cache']['backend'] instanceof Cache) {
			$this->cacheBackend = $params['cache']['backend'];
		}

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

	public function logout() {
		if ($this->cacheBackend !== null) {
			$this->cacheBackend->save();
		}

		parent::logout();
	}
}
