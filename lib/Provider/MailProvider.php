<?php
declare(strict_types=1);

/**
* @copyright Copyright (c) 2023 Sebastian Krupinski <krupinski01@gmail.com>
*
* @author Sebastian Krupinski <krupinski01@gmail.com>
*
* @license AGPL-3.0-or-later
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
namespace OCA\Mail\Provider;

use OCP\Mail\Provider\IProvider;
use OCP\Mail\Provider\IService;
use OCA\Mail\Service\AccountService;

use OCA\Mail\AppInfo\Application;

class MailProvider implements IProvider {

	private $_ServicesService;
	private ?array $_ServiceCollection = null;

	public function __construct(AccountService $AccountService) {
		
		$this->_ServicesService = $AccountService;

	}

	/**
	 * An arbitrary unique text string identifying this provider
	 * @since 1.0.0
	 */
	public function id(): string {

		return 'mail-application';

	}

	/**
	 * The localized human frendly name of this provider
	 * @since 1.0.0
	 */
	public function label(): string {

		return 'Mail App';

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function hasServices(string $uid): bool {

		return (count($this->listServices($uid)) > 0);

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function listServices(string $uid): array {

		// evaluate if collection of services is null
		if (!is_array($this->_ServiceCollection)) {
			// define services collection
			$this->_ServiceCollection = [];
			// retrieve list of services from data store
			$services = $this->_ServicesService->findByUserId($uid);
			// add services to collection
			foreach ($services as $entry) {
				// extract values
				$id = (string) $entry->getId();
				$label = $entry->getName();
				$address = $entry->getEmail();
				$identity = new MailServiceIdentity();
				$location = new MailServiceLocation();
				// add service to collection
				$this->_ServiceCollection[] = new MailService($id, $label, $address, $identity, $location);
			}
		}
		// return list of services for user
		return $this->_ServiceCollection;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function createService(string $uid, IService $service): string {

		return '';

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function modifyService(string $uid, IService $service): string {

		return '';

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function deleteService(string $uid, IService $service): bool {

		return false;

	}
	
}
