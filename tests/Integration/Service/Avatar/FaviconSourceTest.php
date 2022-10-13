<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Tests\Integration\Service\Avatar;

use OC;
use OCA\Mail\Service\Avatar\AvatarFactory;
use OCA\Mail\Service\Avatar\FaviconSource;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Vendor\Favicon\Favicon;
use OCP\Files\IMimeTypeDetector;
use OCP\Http\Client\IClientService;
use OCP\IServerContainer;

class FaviconSourceTest extends TestCase {
	/** @var IServerContainer */
	private $serverContainer;

	protected function setUp(): void {
		parent::setUp();

		$this->serverContainer = OC::$server;
	}

	public function testFetchNoCacheFiles() {
		$email = 'noreply@duckduckgo.com';
		$avatarFactory = $this->serverContainer->query(AvatarFactory::class);
		/** @var IClientService $clientService */
		$clientService = $this->serverContainer->query(IClientService::class);
		/** @var IMimeTypeDetector $clientService */
		$mimeDetector = $this->serverContainer->query(IMimeTypeDetector::class);
		$source = new FaviconSource(
			$clientService,
			new Favicon(),
			$mimeDetector
		);

		$avatar = $source->fetch($email, $avatarFactory);

		$this->assertNotNull($avatar);
	}
}
