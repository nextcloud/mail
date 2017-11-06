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

namespace OCA\Mail\Tests\Service\Avatar;

use Mpclarkson\IconScraper\Icon;
use Mpclarkson\IconScraper\Scraper;
use OCA\Mail\Service\Avatar\FaviconSource;
use OCA\Mail\Tests\TestCase;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use PHPUnit_Framework_MockObject_MockObject;

class FaviconSourceTest extends TestCase {

	/** @var IClientService|PHPUnit_Framework_MockObject_MockObject */
	private $clientService;

	/** @var Scraper|PHPUnit_Framework_MockObject_MockObject */
	private $scraper;

	/** @var FaviconSource */
	private $source;

	protected function setUp() {
		parent::setUp();

		$this->clientService = $this->createMock(IClientService::class);
		$this->scraper = $this->createMock(Scraper::class);

		$this->source = new FaviconSource($this->clientService, $this->scraper);
	}

	public function testFetchNoIconsFound() {
		$email = 'hey@jancborchardt.net';
		$uid = 'john';
		$this->scraper->expects($this->once())
			->method('get')
			->with('jancborchardt.net')
			->willReturn([]);

		$avatar = $this->source->fetch($email, $uid);

		$this->assertNull($avatar);
	}

	public function testFetchSingleIcon() {
		$email = 'hey@jancborchardt.net';
		$uid = 'john';
		$icon = $this->createMock(Icon::class);
		$this->scraper->expects($this->once())
			->method('get')
			->with('jancborchardt.net')
			->willReturn([$icon]);
		$icon->expects($this->once())
			->method('getHref')
			->willReturn('https://domain.tld/favicon.ico');
		$client = $this->createMock(IClient::class);
		$this->clientService->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$response = $this->createMock(IResponse::class);
		$client->expects($this->once())
			->method('get')
			->with('https://domain.tld/favicon.ico')
			->willReturn($response);
		$response->expects($this->once())
			->method('getBody')
			->willReturn('data');

		$avatar = $this->source->fetch($email, $uid);

		$this->assertSame('https://domain.tld/favicon.ico', $avatar);
	}

	public function testFetchEmptyIcon() {
		$email = 'hey@jancborchardt.net';
		$uid = 'john';
		$icon = $this->createMock(Icon::class);
		$this->scraper->expects($this->once())
			->method('get')
			->with('jancborchardt.net')
			->willReturn([$icon]);
		$icon->expects($this->once())
			->method('getHref')
			->willReturn('https://domain.tld/favicon.ico');
		$client = $this->createMock(IClient::class);
		$this->clientService->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$response = $this->createMock(IResponse::class);
		$client->expects($this->once())
			->method('get')
			->with('https://domain.tld/favicon.ico')
			->willReturn($response);
		$response->expects($this->once())
			->method('getBody')
			->willReturn('');

		$avatar = $this->source->fetch($email, $uid);

		$this->assertNull($avatar);
	}

}
