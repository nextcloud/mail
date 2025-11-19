<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Notification;

use OCA\Mail\AppInfo\Application;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

class Notifier implements INotifier {
	private readonly IFactory $factory;

	public function __construct(IFactory $factory) {
		$this->factory = $factory;
	}

	#[\Override]
	public function getID(): string {
		return Application::APP_ID;
	}

	/**
	 * Human-readable name describing the notifier
	 */
	#[\Override]
	public function getName(): string {
		return $this->factory->get(Application::APP_ID)->t('Mail');
	}


	#[\Override]
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== Application::APP_ID) {
			// Not my app => throw
			throw new UnknownNotificationException();
		}

		// Read the language from the notification
		$l = $this->factory->get(Application::APP_ID, $languageCode);

		switch ($notification->getSubject()) {
			// Deal with known subjects
			case 'quota_depleted':
				$parameters = $notification->getSubjectParameters();
				$notification->setRichSubject($l->t('You are reaching your mailbox quota limit for {account_email}'), [
					'account_email' => [
						'type' => 'highlight',
						'id' => $parameters['id'],
						'name' => $parameters['account_email']
					]
				]);
				$messageParameters = $notification->getMessageParameters();
				$notification->setRichMessage($l->t('You are currently using {percentage} of your mailbox storage. Please make some space by deleting unneeded emails.'),
					[
						'percentage' => [
							'type' => 'highlight',
							'id' => $messageParameters['id'],
							'name' => $messageParameters['quota_percentage'] . '%',
						]
					]);
				break;
			default:
				throw  new UnknownNotificationException();
		}

		return $notification;
	}
}
