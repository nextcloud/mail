<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Notification;

use OCA\Mail\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

class Notifier implements INotifier {
	private IFactory $factory;
	private IURLGenerator $url;

	public function __construct(IFactory $factory,
		IURLGenerator $url) {
		$this->factory = $factory;
		$this->url = $url;
	}

	#[\Override]
	public function getID(): string {
		return Application::APP_ID;
	}

	/**
	 * Human-readable name describing the notifier
	 * @return string
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
							'name' => (string)$messageParameters['quota_percentage'] . '%',
						]
					]);
				break;
			case 'account_delegation':
				$parameters = $notification->getSubjectParameters();
				$messageParameters = $notification->getMessageParameters();
				$delegated = $messageParameters['delegated'];
				if ($delegated) {
					$notification->setRichSubject($l->t('{account_email} has been delegated to you'), [
						'account_email' => [
							'type' => 'highlight',
							'id' => (string)$parameters['id'],
							'name' => $parameters['account_email']
						]
					]);
					$notification->setRichMessage($l->t('{user} delegated {account} to you'),
						[
							'user' => [
								'type' => 'user',
								'id' => $messageParameters['current_user_id'],
								'name' => $messageParameters['current_user_display_name'],
							],
							'account' => [
								'type' => 'highlight',
								'id' => (string)$messageParameters['id'],
								'name' => $messageParameters['account_email']
							]
						]);
				} else {
					$notification->setRichSubject($l->t('{account_email} is no longer delegated to you'), [
						'account_email' => [
							'type' => 'highlight',
							'id' => (string)$parameters['id'],
							'name' => $parameters['account_email']
						]
					]);
					$notification->setRichMessage($l->t('{user} revoked delagation for {account}'),
						[
							'user' => [
								'type' => 'user',
								'id' => $messageParameters['current_user_id'],
								'name' => $messageParameters['current_user_display_name'],
							],
							'account' => [
								'type' => 'highlight',
								'id' => (string)$messageParameters['id'],
								'name' => $messageParameters['account_email']
							]
						]);
				}

				break;
			default:
				throw new UnknownNotificationException();
		}

		return $notification;
	}
}
