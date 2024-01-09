<?php

declare(strict_types=1);

/*
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
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
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IMemcache;
use function floor;

/**
 * "Decorator" around Horde's IMAP client to add auth error rate limiting
 *
 * This is not a real decorator because the component to decorate doesn't have
 * an interface, making it hard to base a decorator on composition.
 * For simplicity the component is decorated by inheritance.
 */
class ImapClientRateLimitingDecorator extends Horde_Imap_Client_Socket {

	private IMemcache $cache;

	private ITimeFactory $timeFactory;
	private string $hash;

	public function __construct(array $params,
		string $hash,
		IMemcache $cache,
		ITimeFactory $timeFactory) {
		parent::__construct($params);
		$this->cache = $cache;
		$this->timeFactory = $timeFactory;
		$this->hash = $hash;
	}

	protected function _login() {
		$now = $this->timeFactory->getTime();
		$window = floor($now / (3 * 60 * 60));
		$cacheKey = $this->hash . $window;

		$counter = $this->cache->get($cacheKey);
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
				$current = $this->cache->inc($cacheKey);
			}
			throw $e;
		}
	}

}
