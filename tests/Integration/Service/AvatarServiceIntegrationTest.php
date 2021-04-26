<?php

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

namespace OCA\Mail\Tests\Integration\Service;

use OC;
use OCA\Mail\Contracts\IAvatarService;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCP\ICache;
use OCP\ICacheFactory;

class AvatarServiceIntegrationTest extends TestCase {

	/** @var IAvatarService */
	private $service;

	private function clearCache() {
		/* @var $cacheFactory ICacheFactory */
		$cacheFactory = OC::$server->query(ICacheFactory::class);
		/* @var $cache ICache */
		$cache = $cacheFactory->createDistributed('mail.avatars');
		$cache->clear();
	}

	protected function setUp(): void {
		parent::setUp();

		$this->clearCache();
		$this->service = OC::$server->query(IAvatarService::class);
	}

	public function testChristophsFavicon() {
		$avatar = $this->service->getAvatar('christoph@winzerhof-wurst.at', 'jan');
		$this->assertNull($avatar); // There is none
	}
}
