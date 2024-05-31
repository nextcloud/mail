<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Sebastian Krupinski <krupinski01@gmail.com>
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

use OCA\Mail\Account;
use OCA\Mail\Service\AccountService;

use OCP\Mail\Provider\Address as MailAddress;
use OCP\Mail\Provider\IProvider;
use OCP\Mail\Provider\IService;

use Psr\Container\ContainerInterface;

class MailProvider implements IProvider {

	private ContainerInterface $container;
	private AccountService $AccountService;
	private ?array $ServiceCollection = [];

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
	 * @since 2024.05.25
	 *
	 * @return string				id of this provider (e.g. UUID or 'IMAP/SMTP' or anything else)
	 */
	public function id(): string {

		return 'mail-application';

	}

	/**
	 * The localized human frendly name of this provider
	 *
	 * @since 2024.05.25
	 *
	 * @return string				label/name of this provider (e.g. Plain Old IMAP/SMTP)
	 */
	public function label(): string {

		return 'Mail Application';

	}

	/**
	 * Determain if any services are configured for a specific user
	 *
	 * @since 2024.05.25
	 *
	 * @return bool 				true if any services are configure for the user
	 */
	public function hasServices(string $uid): bool {

		return (count($this->listServices($uid)) > 0);

	}

	/**
	 * retrieve collection of services for a specific user
	 *
	 * @since 2024.05.25
	 *
	 * @return array<string,IService>		collection of service objects
	 */
	public function listServices(string $uid): array {

		try {
			// retrieve service(s) details from data store
			$accounts = $this->AccountService->findByUserId($uid);
		} catch (\Throwable $th) {
			return [];
		}
		// construct temporary collection
		$services = [];
		// add services to collection
		foreach ($accounts as $entry) {
			// extract values
			$id = (string) $entry->getId();
			$label = $entry->getName();
			$address = new MailAddress($entry->getEmail(), $entry->getName());
			$identity = new MailServiceIdentity();
			$location = new MailServiceLocation();
			// add service to collection
			$services[] = new MailService($this->container, $uid, $id, $label, $address, $identity, $location);
		}
		// return list of services for user
		return $services;

	}

	/**
	 * Retrieve a service with a specific id
	 *
	 * @since 2024.05.25
	 *
	 * @param string $uid				user id
	 * @param string $id				service id
	 *
	 * @return IService|null			returns service object or null if non found
	 */
	public function findServiceById(string $uid, string $id): IService | null {

		// evaluate if id is a number
		if (is_numeric($id)) {
			try {
				// retrieve service details from data store
				$account = $this->AccountService->find($uid, (int) $id);
			} catch(\Throwable $th) {
				return null;
			}
		}
		// evaliate if service details where found
		if ($account instanceof Account) {
			// extract values
			$id = (string) $account->getId();
			$label = $account->getName();
			$address = new MailAddress($account->getEmail(), $account->getName());
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
	 *
	 * @return IService					returns service object or null if non found
	 */
	public function findServiceByAddress(string $uid, string $address): IService | null {

		try {
			// retrieve service details from data store
			$accounts = $this->AccountService->findByUserIdAndAddress($uid, $address);
		} catch(\Throwable $th) {
			return null;
		}
		// evaliate if service details where found
		if (is_array($accounts) && count($accounts) > 0 && $accounts[0] instanceof Account) {
			// extract values
			$id = (string) $accounts[0]->getId();
			$label = $accounts[0]->getName();
			$address = new MailAddress($accounts[0]->getEmail(), $accounts[0]->getName());
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
	 * @since 2024.05.25
	 *
	 * @param string $uid			user id of user to configure service for
	 * @param IService $service 	service configuration object
	 *
	 * @return string				id of created service
	 */
	public function createService(string $uid, IService $service): string {

		return '';

	}

	/**
	 * modify a service configuration for a specific user
	 *
	 * @since 2024.05.25
	 *
	 * @param string $uid			user id of user to configure service for
	 * @param IService $service 	service configuration object
	 *
	 * @return string				id of modifided service
	 */
	public function modifyService(string $uid, IService $service): string {

		return '';

	}

	/**
	 * delete a service configuration for a specific user
	 *
	 * @since 2024.05.25
	 *
	 * @param string $uid			user id of user to delete service for
	 * @param IService $service 	service configuration object
	 *
	 * @return bool					status of delete action
	 */
	public function deleteService(string $uid, IService $service): bool {

		return false;

	}

}
