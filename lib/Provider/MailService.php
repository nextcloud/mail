<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Provider;

use OCA\Mail\Provider\Command\MessageSend;
use OCP\Mail\Provider\Address;
use OCP\Mail\Provider\Exception\SendException;
use OCP\Mail\Provider\IAddress;
use OCP\Mail\Provider\IMessage;
use OCP\Mail\Provider\IMessageSend;
use OCP\Mail\Provider\IService;
use OCP\Mail\Provider\Message;

use Psr\Container\ContainerInterface;

class MailService implements IService, IMessageSend {

	protected array $serviceSecondaryAddresses = [];
	protected array $serviceAbilities = [];

	public function __construct(
		protected ContainerInterface $container,
		protected string $userId = '',
		protected string $serviceId = '',
		protected string $serviceLabel = '',
		protected IAddress $servicePrimaryAddress = new Address(),
	) {
		$this->serviceAbilities = ['MessageSend' => true];
	}

	/**
	 * Arbitrary unique text string identifying this service
	 *
	 * @since 4.0.0
	 *
	 * @return string id of this service (e.g. 1 or service1 or anything else)
	 */
	public function id(): string {
		return $this->serviceId;
	}

	/**
	 * Checks if a service is able of performing an specific action
	 *
	 * @since 4.0.0
	 *
	 * @param string $value required ability e.g. 'MessageSend'
	 *
	 * @return bool true/false if ability is supplied and found in collection
	 */
	public function capable(string $value): bool {
		// evaluate if required ability exists
		if (isset($this->serviceAbilities[$value])) {
			return (bool)$this->serviceAbilities[$value];
		}
		
		return false;
	}

	/**
	 * Retrieves a collection of what actions a service can perform
	 *
	 * @since 4.0.0
	 *
	 * @return array collection of abilities otherwise empty collection
	 */
	public function capabilities(): array {
		return $this->serviceAbilities;
	}

	/**
	 * Gets the localized human friendly name of this service
	 *
	 * @since 4.0.0
	 *
	 * @return string label/name of service (e.g. ACME Company Mail Service)
	 */
	public function getLabel(): string {
		return $this->serviceLabel;
	}

	/**
	 * Sets the localized human friendly name of this service
	 *
	 * @since 4.0.0
	 *
	 * @param string $value label/name of service (e.g. ACME Company Mail Service)
	 *
	 * @return self return this object for command chaining
	 */
	public function setLabel(string $value): self {
		$this->serviceLabel = $value;
		return $this;
	}

	/**
	 * Gets the primary mailing address for this service
	 *
	 * @since 4.0.0
	 *
	 * @return IAddress mail address object
	 */
	public function getPrimaryAddress(): IAddress {
		// retrieve and return primary service address
		return $this->servicePrimaryAddress;
	}

	/**
	 * Sets the primary mailing address for this service
	 *
	 * @since 4.0.0
	 *
	 * @param IAddress $value mail address object
	 *
	 * @return self return this object for command chaining
	 */
	public function setPrimaryAddress(IAddress $value): self {
		$this->servicePrimaryAddress = $value;
		return $this;
	}

	/**
	 * Gets the secondary mailing addresses (aliases) collection for this service
	 *
	 * @since 4.0.0
	 *
	 * @return array<int, IAddress> collection of mail address object [IAddress, IAddress]
	 */
	public function getSecondaryAddresses(): array {
		// retrieve and return secondary service addresses (aliases) collection
		return $this->serviceSecondaryAddresses;
	}

	/**
	 * Sets the secondary mailing addresses (aliases) for this service
	 *
	 * @since 4.0.0
	 *
	 * @param IAddress ...$value collection of one or more mail address object
	 *
	 * @return self return this object for command chaining
	 */
	public function setSecondaryAddresses(IAddress ...$value): self {
		$this->serviceSecondaryAddresses = $value;
		return $this;
	}

	/**
	 * Construct a new fresh message object
	 *
	 * @since 30.0.0
	 *
	 * @return IMessage fresh message object
	 */
	public function initiateMessage(): IMessage {
		return new Message();
	}

	/**
	 * Sends an outbound message
	 *
	 * @since 4.0.0
	 *
	 * @param IMessage $message mail message object with all required parameters to send a message
	 * @param array $options array of options reserved for future use
	 *
	 * @throws SendException on failure, check message for reason
	 */
	public function sendMessage(IMessage $message, array $options = []): void {
		/** @var MessageSend $cmd */
		$cmd = $this->container->get(MessageSend::class);
		// perform action
		$cmd->perform($this->userId, $this->serviceId, $message, $options);
	}

}
