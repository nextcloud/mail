<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Integration;

use OCA\Mail\AppInfo\Application;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

class GoogleIntegration {
	private ITimeFactory $timeFactory;
	private IConfig $config;
	private ICrypto $crypto;
	private IClientService $clientService;
	private LoggerInterface $logger;

	public function __construct(ITimeFactory $timeFactory,
								IConfig $config,
								ICrypto $crypto,
								IClientService $clientService,
								LoggerInterface $logger) {
		$this->timeFactory = $timeFactory;
		$this->clientService = $clientService;
		$this->crypto = $crypto;
		$this->config = $config;
		$this->logger = $logger;
	}

	public function configure(string $clientId, string $clientSecret): void {
		$this->config->setAppValue(
			Application::APP_ID,
			'google_oauth_client_id',
			$clientId
		);
		$this->config->setAppValue(
			Application::APP_ID,
			'google_oauth_client_secret',
			$this->crypto->encrypt($clientSecret),
		);
	}

	public function unlink() {
		$this->config->deleteAppValue(
			Application::APP_ID,
			'google_oauth_client_id',
		);
		$this->config->deleteAppValue(
			Application::APP_ID,
			'google_oauth_client_secret',
		);
	}

	public function getClientId(): ?string {
		$value = $this->config->getAppValue(Application::APP_ID, 'google_oauth_client_id');
		if ($value === '') {
			return null;
		}
		return $value;
	}
}
