<?php

/**
 * @copyright 2021 Gregor Mitzka <gregor.mitzka@gmail.com>
 *
 * @author 2021 Gregor Mitzka <gregor.mitzka@gmail.com>
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

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\Avatar\Avatar;
use OCA\Mail\Service\Avatar\AvatarFactory;
use OCA\Mail\Service\Avatar\BimiSource;
use OCA\Mail\Service\Avatar\DnsRecordService;
use OCP\Files\IMimeTypeDetector;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use PHPUnit_Framework_MockObject_MockObject;

class BimiSourceTest extends TestCase {

	/** @var IClientService|PHPUnit_Framework_MockObject_MockObject */
	private $clientService;

	/** @var BimiSource */
	private $source;

	/** @var IMimeTypeDetector|MockObject */
	private $mimeDetector;

	protected function setUp(): void {
		parent::setUp();

		$this->clientService = $this->createMock(IClientService::class);

		$this->mimeDetector = $this->createMock(IMimeTypeDetector::class);

		$this->source = new BimiSource(
			$this->clientService,
			$this->mimeDetector,
			$this->getDnsRecordServiceMock()
		);
	}

	public function testFetchExisting() {
		$this->mimeDetector
			->expects($this->once())
			->method('detectString')
			->with('data')
			->willReturn('image/svg+xml');

		$response = $this->createMock(IResponse::class);
		$response
			->expects($this->once())
			->method('getBody')
			->willReturn(
				file_get_contents(
					sprintf(
						'%s/valid-svg-ps.svg',
						__DIR__
					)
				)
			);

		$client = $this->createMock(IClient::class);
		$client
			->expects($this->once())
			->method('get')
			->with('https://example.org/bimi.svg')
			->willReturn($response);

		$this->clientService
			->expects($this->once())
			->method('newClient')
			->willReturn($client);

		$avatar = new Avatar('https://example.org/bimi.svg');

		$avatarFactory = $this->createMock(AvatarFactory::class);
		$avatarFactory
			->expects($this->once())
			->method('createExternal')
			->with(
				'https://example.org/bimi.svg',
				'image/svg+xml'
			)
			->willReturn($avatar);

		$email = 'foo@example.org';

		$actualAvatar = $this->source->fetch(
			$email,
			$avatarFactory
		);

		$this->assertEquals(
			$avatar,
			$actualAvatar
		);
	}

	protected function getDnsRecordServiceMock() {
		$mock = $this->createMock(
			DnsRecordService::class
		);

		$mock
			->expects($this->once())
			->method('getRecords')
			->with(
				'default._bimi.example.org',
				DNS_TXT
			)
			->willReturn([
				[
					'host' => 'default._bimi.example.org',
					'class' => 'IN',
					'ttl' => 1337,
					'type' => 'TXT',
					'txt' => 'v=BIMI1; l=https://example.org/bimi.svg',
					'entries' => [
						'v=BIMI1; l=https://example.org/bimi.svg',
					],
				]
			]);

		return $mock;
	}
}
