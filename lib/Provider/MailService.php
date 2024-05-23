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

use OCP\Mail\Provider\IService;
use OCP\Mail\Provider\IMessageSend;
use OCP\Mail\Provider\IServiceIdentity;
use OCP\Mail\Provider\IServiceLocation;
use OCP\Mail\Provider\IMessage;
use OCP\Mail\Provider\IAddress;

use OCA\Mail\AppInfo\Application;

class MailService implements IService, IMessageSend {

	private string $userId;
	private string $serviceId;
	private string $serviceLabel;
	private IAddress $servicePrimaryAddress;
	private ?array $serviceSecondaryAddress;
	private ?MailServiceIdentity $serviceIdentity;
	private ?MailServiceLocation $serviceLocation;

	public function __construct(
		ContainerInterface $container,
		string $uid,
		string $sid,
		string $label,
		IAddress $primaryAddress,
		?MailServiceIdentity $identity = null,
		?MailServiceLocation $location = null
	) {

		$this->container = $container;
		$this->userId = $uid;
		$this->serviceId = $sid;
		$this->serviceLabel = $label;
		$this->servicePrimaryAddress = $primaryAddress;
		$this->serviceIdentity = $identity;
		$this->serviceLocation = $location;

	}

	/**
	 * An arbitrary unique text string identifying this service
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function id(): string {

		return $this->serviceId;

	}

	/**
	 * The localized human frendly name of this provider
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function getLabel(): string {

		return $this->serviceLabel;

	}

	/**
	 * The localized human frendly name of this provider
	 * 
	 * @since 1.0.0
	 */
	public function setLabel(string $value): self {

		$this->serviceLabel = $value;
		return $this;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function getIdentity(): IServiceIdentity | null {

		return $this->serviceIdentity;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function setIdentity(IServiceIdentity $value): self {

		$this->serviceIdentity = $value;
		return $this;
	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function getLocation(): IServiceLocation | null {

		return $this->serviceLocation;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function setLocation(IServiceLocation $value): self {

		$this->serviceLocation = $value;
		return $this;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function getPrimaryAddress(): IAddress {

		// retrieve and return primary service address
		return $this->servicePrimaryAddress;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function setPrimaryAddress(IAddress $value): self {

		$this->servicePrimaryAddress = $value;
		return $this;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function getSecondaryAddress(): array | null {

		// retrieve and return secondary service addressess (aliases) collection
		return $this->serviceSecondaryAddress;

	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function setSecondaryAddress(IAddress ...$value): self {

		$this->serviceSecondaryAddress = $value;
		return $this;

	}

	public function messageSend(IMessage $message, array $option = []): void {

		// load action
		$cmd = $this->container->get(\OCA\Mail\Provider\Command\MessageSend::class);
		// perform action
		$cmd->perform($this->userId, $this->serviceId, $message, $option);

	}

}
