<?php

declare(strict_types=1);
/**
 * Calendar App
 *
 * @copyright 2023 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Notification;

use OCA\Mail\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {
	private IFactory $factory;
	private IURLGenerator $url;

	public function __construct(IFactory $factory,
		IURLGenerator $url) {
		$this->factory = $factory;
		$this->url = $url;
	}

	public function getID(): string {
		return Application::APP_ID;
	}

	/**
	 * Human-readable name describing the notifier
	 * @return string
	 */
	public function getName(): string {
		return $this->factory->get(Application::APP_ID)->t('Mail');
	}


	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== Application::APP_ID) {
			// Not my app => throw
			throw new \InvalidArgumentException();
		}

		// Read the language from the notification
		$l = $this->factory->get(Application::APP_ID, $languageCode);

		switch ($notification->getSubject()) {
			// Deal with known subjects
			case 'quota_depleted':
				$parameters = $notification->getSubjectParameters();
				$notification->setRichSubject($l->t('You are reaching your mailbox quota limit for {account}'), [
					'account' => [
						'type' => 'highlight',
						'id' => $parameters['id'],
						'name' => $parameters['account_email']
					]
				]);
				$notification->setParsedSubject($notification->getRichSubject());

				$messageParameters = $notification->getMessageParameters();
				$notification->setRichMessage($l->t('You are currently using {percentage} of your mailbox storage. Please make some space by deleting unneeded emails.'),
					[
						'percentage' => [
							'type' => 'highlight',
							'id' => $messageParameters['id'],
							'name' => (string)$messageParameters['quota_percentage'] . '%',
						]
					]);
				$notification->setParsedMessage($notification->getParsedMessage());
				break;
			default:
				throw  new \InvalidArgumentException();
		}

		return $notification;
	}
}
