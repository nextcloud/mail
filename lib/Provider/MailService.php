<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Mail\Provider;

use OCP\Mail\Provider\IAddress;
use OCP\Mail\Provider\IMessage;
use OCP\Mail\Provider\IMessageSend;
use OCP\Mail\Provider\IService;
use OCP\Mail\Provider\IServiceIdentity;
use OCP\Mail\Provider\IServiceLocation;

use Psr\Container\ContainerInterface;

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
	 * @since 2024.05.25
	 *
	 * @return string						id of this service (e.g. 1 or service1 or anything else)
	 */
	public function id(): string {

		return $this->serviceId;

	}

	/**
	 * checks or retrieves what capabilites the service has
	 *
	 * @since 2024.05.25
	 *
	 * @param string $ability				required ability e.g. 'MessageSend'
	 *
	 * @return bool|array					true/false if ability is supplied, collection of abilities otherwise
	 */
	public function capable(?string $ability = null): bool | array {

		// define all abilities
		$abilities = [
			'MessageSend' => true,
		];
		// evaluate if required ability was specified
		if (isset($ability)) {
			return (isset($abilities[$ability]) ? (bool) $abilities[$ability] : false);
		} else {
			return $abilities;
		}

	}

	/**
	 * gets the localized human frendly name of this service
	 *
	 * @since 2024.05.25
	 *
	 * @return string						label/name of service (e.g. ACME Company Mail Service)
	 */
	public function getLabel(): string {

		return $this->serviceLabel;

	}

	/**
	 * sets the localized human frendly name of this service
	 *
	 * @since 2024.05.25
	 *
	 * @param string $value					label/name of service (e.g. ACME Company Mail Service)
	 *
	 * @return self                         return this object for command chaining
	 */
	public function setLabel(string $value): self {

		$this->serviceLabel = $value;
		return $this;

	}

	/**
	 * gets service itentity
	 *
	 * @since 2024.05.25
	 *
	 * @return IServiceIdentity				service identity object
	 */
	public function getIdentity(): IServiceIdentity | null {

		return $this->serviceIdentity;

	}

	/**
	 * sets service identity
	 *
	 * @since 2024.05.25
	 *
	 * @param IServiceIdentity $identity	service identity object
	 *
	 * @return self                         return this object for command chaining
	 */
	public function setIdentity(IServiceIdentity $value): self {

		$this->serviceIdentity = $value;
		return $this;
	}

	/**
	 * gets service location
	 *
	 * @since 2024.05.25
	 *
	 * @return IServiceLocation				service location object
	 */
	public function getLocation(): IServiceLocation | null {

		return $this->serviceLocation;

	}

	/**
	 * sets service location
	 *
	 * @since 2024.05.25
	 *
	 * @param IServiceLocation $location	service location object
	 *
	 * @return self                         return this object for command chaining
	 */
	public function setLocation(IServiceLocation $value): self {

		$this->serviceLocation = $value;
		return $this;

	}

	/**
	 * gets the primary mailing address for this service
	 *
	 * @since 2024.05.25
	 *
	 * @return IAddress						mail address object
	 */
	public function getPrimaryAddress(): IAddress {

		// retrieve and return primary service address
		return $this->servicePrimaryAddress;

	}

	/**
	 * sets the primary mailing address for this service
	 *
	 * @since 2024.05.25
	 *
	 * @param IAddress $value				mail address object
	 *
	 * @return self                         return this object for command chaining
	 */
	public function setPrimaryAddress(IAddress $value): self {

		$this->servicePrimaryAddress = $value;
		return $this;

	}

	/**
	 * gets the secondary mailing addresses (aliases) collection for this service
	 *
	 * @since 2024.05.25
	 *
	 * @return array<int, IAddress>			collection of mail address objects
	 */
	public function getSecondaryAddress(): array | null {

		// retrieve and return secondary service addressess (aliases) collection
		return $this->serviceSecondaryAddress;

	}

	/**
	 * sets the secondary mailing addresses (aliases) for this service
	 *
	 * @since 2024.05.25
	 *
	 * @param IAddress ...$value				collection of or one or more mail address objects
	 *
	 * @return self                         	return this object for command chaining
	 */
	public function setSecondaryAddress(IAddress ...$value): self {

		$this->serviceSecondaryAddress = $value;
		return $this;

	}

	/**
	 * Sends an outbound message
	 *
	 * @since 2024.05.25
	 *
	 * @param IMessage $message			mail message object with all required parameters to send a message
	 *
	 * @param array $options			array of options reserved for future use
	 */
	public function messageSend(IMessage $message, array $option = []): void {

		// load action
		$cmd = $this->container->get(\OCA\Mail\Provider\Command\MessageSend::class);
		// perform action
		$cmd->perform($this->userId, $this->serviceId, $message, $option);

	}

}
