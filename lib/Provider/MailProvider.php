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

use Psr\Container\ContainerInterface;

use OCP\Mail\Provider\IProvider;
use OCP\Mail\Provider\IService;
use OCP\Mail\Provider\Address as MailAddress;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Service\AccountService;

class MailProvider implements IProvider {

	private ContainerInterface $container;
	private AccountService $AccountService;
	private ?array $ServiceCollection = null;

	public function __construct(
		ContainerInterface $container,
		AccountService $AccountService
	) {
		
		$this->container = $container;
		$this->AccountService = $AccountService;

	}

	/**
	 * An arbitrary unique text string identifying this provider
	 * 
	 * @since 30.0.0
	 * @return string				id of this provider (e.g. UUID or 'IMAP/SMTP' or anything else)
	 */
	public function id(): string {

		return 'mail-application';

	}

	/**
	 * The localized human frendly name of this provider
	 * 
	 * @since 30.0.0
	 * @return string				label/name of this provider (e.g. Plain Old IMAP/SMTP)
	 */
	public function label(): string {

		return 'Mail Application';

	}

	/**
	 * Determain if any services are configured for a specific user
	 * 
	 * @since 30.0.0
	 * @return bool 				true if any services are configure for the user
	 */
	public function hasServices(string $uid): bool {

		return (count($this->listServices($uid)) > 0);

	}

	/**
	 * retrieve collection of services for a specific user
	 * 
	 * @since 30.0.0
	 * @return array<string,IService>		collection of service objects
	 */
	public function listServices(string $uid): array {

		// evaluate if collection of services is null
		if (!is_array($this->ServiceCollection)) {
			// define services collection
			$this->ServiceCollection = [];
			// retrieve service(s) details from data store
			$services = $this->AccountService->findByUserId($uid);
			// add services to collection
			foreach ($services as $entry) {
				// extract values
				$id = (string) $entry->getId();
				$label = $entry->getName();
				$address = new MailAddress($entry->getEmail(), $entry->getName());
				$identity = new MailServiceIdentity();
				$location = new MailServiceLocation();
				// add service to collection
				$this->ServiceCollection[$id] = new MailService($this->container, $uid, $id, $label, $address, $identity, $location);
			}
		}
		// return list of services for user
		return $this->ServiceCollection;

	}

	/**
	 * Retrieve a service with a specific id
	 * 
	 * @since 30.0.0
	 * @param string $uid				user id
	 * @param string $id				service id
	 * @return IService|null			returns service object or null if non found
	 */
	public function findServiceById(string $uid, string $id): IService | null {

		// evaluate if id is a number
		if (is_numeric($id)) {
			// retrieve service details from data store
			$service = $this->AccountService->find($uid,(int) $id);
		}
		// evaliate if service details where found
		if ($service !== null) {
			// extract values
			$id = (string) $service->getId();
			$label = $service->getName();
			$address = new MailAddress($service->getEmail(), $service->getName());
			$identity = new MailServiceIdentity();
			$location = new MailServiceLocation();
			// return mail service instance
			return (new MailService($this->container, $uid, $id, $label, $address, $identity, $location));
		}

		return null;
		
	}

	/**
	 * Retrieve a service for a specific mail address
	 * 
	 * @since 2024.05.25
	 * 
	 * @param string $uid				user id
	 * @param string $address			mail address (e.g. test@example.com)
	 * @return IService					returns service object or null if non found
	 */
	public function findServiceByAddress(string $uid, string $address): IService | null {

		// retrieve service details from data store
		$services = $this->AccountService->findByUserIdAndAddress($uid, $address);
		// evaliate if service details where found
		if (is_array($services) && count($services) > 0) {
			// extract values
			$id = (string) $services[0]->getId();
			$label = $services[0]->getName();
			$address = new MailAddress($services[0]->getEmail(), $services[0]->getName());
			$identity = new MailServiceIdentity();
			$location = new MailServiceLocation();
			// return mail service instance
			return (new MailService($this->container, $uid, $id, $label, $address, $identity, $location));
		}

		return null;

	}

	/**
	 * create a service configuration for a specific user
	 * 
	 * @since 30.0.0
	 * @param string $uid			user id of user to configure service for
	 * @param IService $service 	service configuration object
	 * @return string				id of created service
	 */
	public function createService(string $uid, IService $service): string {

		return '';

	}

	/**
	 * modify a service configuration for a specific user
	 * 
	 * @since 30.0.0
	 * @param string $uid			user id of user to configure service for
	 * @param IService $service 	service configuration object
	 * @return string				id of modifided service
	 */
	public function modifyService(string $uid, IService $service): string {

		return '';

	}

	/**
	 * delete a service configuration for a specific user
	 * 
	 * @since 30.0.0
	 * @param string $uid			user id of user to delete service for
	 * @param IService $service 	service configuration object
	 * @return bool					status of delete action
	 */
	public function deleteService(string $uid, IService $service): bool {

		return false;

	}
	
}
