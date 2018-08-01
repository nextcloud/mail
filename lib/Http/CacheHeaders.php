<?php

declare(strict_types=1);

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 *
 */

namespace OCA\Mail\Http;

use DateInterval;
use DateTime;
use OCP\AppFramework\Utility\ITimeFactory;

trait CacheHeaders {

	public function setCacheHeaders(int $cacheFor, ITimeFactory $timeFactory) {
		$this->cacheFor(7 * 24 * 60 * 60);

		$expires = new DateTime();
		$expires->setTimestamp($timeFactory->getTime());
		$expires->add(new DateInterval('PT' . $cacheFor . 'S'));
		$this->addHeader('Expires', $expires->format(DateTime::RFC1123));

		$this->addHeader('Pragma', 'cache');
	}

}
