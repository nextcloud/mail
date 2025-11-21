<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Provider;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Provider\Command\MessageSend;
use OCA\Mail\TaskProcessing\MailSendTask;
use OCP\Mail\Provider\Address;
use OCP\Mail\Provider\Exception\SendException;
use OCP\Mail\Provider\IAddress;
use OCP\Mail\Provider\IMessage;
use OCP\Mail\Provider\IMessageSend;
use OCP\Mail\Provider\IService;
use OCP\Mail\Provider\Message;
use OCP\TaskProcessing\IManager as TaskProcessingManager;
use OCP\TaskProcessing\Task;
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
	#[\Override]
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
	#[\Override]
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
	#[\Override]
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
	#[\Override]
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
	#[\Override]
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
	#[\Override]
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
	#[\Override]
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
	#[\Override]
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
	#[\Override]
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
	#[\Override]
	public function initiateMessage(): IMessage {
		return new Message();
	}

	/**
	 * Sends an outbound message
	 *
	 * @since 4.0.0
	 *
	 * @param IMessage $message mail message object with all required parameters to send a message
	 * @param array{sendMode?:'instant'|'queued'|'deferred',sendDelay?:int} $options array of options reserved for future use
	 *
	 * $options['sendMode'] can be:
	 * - 'instant': sends the message immediately (connects to the mail service right away)
	 * - 'queued': adds the message to the task processing queue to be sent as soon as possible
	 * - 'deferred': adds the message to the task processing queue to be sent after a delay
	 * $options['sendDelay'] is only applicable if 'sendMode' is 'deferred' and specifies the delay in seconds before sending the message
	 *
	 * @throws SendException on failure, check message for reason
	 */
	#[\Override]
	public function sendMessage(IMessage $message, array $options = []): void {
		// if send mode is not set, default to queued
		if (!isset($options['sendMode'])) {
			$options['sendMode'] = 'queued';
		}
		// if send mode is not instant use task processing this sends the message as soon as possible
		if ($options['sendMode'] !== 'instant') {
			$taskProcessingManager = $this->container->get(TaskProcessingManager::class);
			$availableTaskTypes = $taskProcessingManager->getAvailableTaskTypes();
			// if task processing is available use it
			if (isset($availableTaskTypes[MailSendTask::ID])) {
				$task = new Task(
					MailSendTask::ID,
					[
						'userId' => $this->userId,
						'serviceId' => $this->serviceId,
						'message' => $message,
						'options' => $options,
					],
					Application::APP_ID,
					null
				);
				
				if ($options['sendMode'] === 'deferred' && isset($options['sendDelay']) && is_int($options['sendDelay'])) {
					$task->setScheduledAt(time() + $options['sendDelay']);
				}

				$taskProcessingManager->scheduleTask($task);
				return;
			}
		}
		// fallback to instant send
		/** @var MessageSend $cmd */
		$cmd = $this->container->get(MessageSend::class);
		// perform action
		$cmd->perform($this->userId, $this->serviceId, $message, $options);
	}

}
