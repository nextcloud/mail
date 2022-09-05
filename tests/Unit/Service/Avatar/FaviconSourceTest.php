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

namespace OCA\Mail\Tests\Unit\Service\Avatar;

use OCA\Mail\Service\Avatar\Avatar;
use OCA\Mail\Service\Avatar\AvatarFactory;
use OCA\Mail\Service\Avatar\FaviconSource;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Vendor\Favicon\Favicon;
use OCP\Files\IMimeTypeDetector;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use PHPUnit\Framework\MockObject\MockObject;

class FaviconSourceTest extends TestCase {
	/** @var IClientService|MockObject */
	private $clientService;

	/** @var Favicon|MockObject */
	private $favicon;

	/** @var IMimeTypeDetector|MockObject */
	private $mimeDetector;

	/** @var FaviconSource */
	private $source;

	protected function setUp(): void {
		parent::setUp();

		$this->clientService = $this->createMock(IClientService::class);
		$this->favicon = $this->createMock(Favicon::class);
		$this->mimeDetector = $this->createMock(IMimeTypeDetector::class);

		$this->source = new FaviconSource($this->clientService, $this->favicon, $this->mimeDetector);
	}

	public function testFetchNoIconsFound() {
		$email = 'hey@jancborchardt.net';
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$this->favicon->expects($this->once())
			->method('get')
			->with('https://jancborchardt.net')
			->willReturn(false);

		$avatar = $this->source->fetch($email, $avatarFactory);

		$this->assertNull($avatar);
	}

	public function testFetchSingleIcon() {
		$email = 'hey@jancborchardt.net';
		$iconUrl = "https://domain.tld/favicon.ic";
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$avatar = new Avatar('https://domain.tld/favicon.ico');
		$this->favicon->expects($this->once())
			->method('get')
			->with('https://jancborchardt.net')
			->willReturn($iconUrl);
		$client = $this->createMock(IClient::class);
		$this->clientService->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$response = $this->createMock(IResponse::class);
		$client->expects($this->once())
			->method('get')
			->with($iconUrl)
			->willReturn($response);
		$response->expects($this->once())
			->method('getBody')
			->willReturn('data');
		$this->mimeDetector->expects($this->once())
			->method('detectString')
			->with('data')
			->willReturn('image/png');
		$avatarFactory->expects($this->once())
			->method('createExternal')
			->with($iconUrl, 'image/png')
			->willReturn($avatar);

		$actualAvatar = $this->source->fetch($email, $avatarFactory);

		$this->assertSame($avatar, $actualAvatar);
	}

	public function testFetchEmptyIcon() {
		$email = 'hey@jancborchardt.net';
		$iconUrl = "https://domain.tld/favicon.ic";
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$this->favicon->expects($this->once())
			->method('get')
			->with('https://jancborchardt.net')
			->willReturn($iconUrl);
		$client = $this->createMock(IClient::class);
		$this->clientService->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$response = $this->createMock(IResponse::class);
		$client->expects($this->once())
			->method('get')
			->with($iconUrl)
			->willReturn($response);
		$response->expects($this->once())
			->method('getBody')
			->willReturn('');

		$avatar = $this->source->fetch($email, $avatarFactory);

		$this->assertNull($avatar);
	}
}
