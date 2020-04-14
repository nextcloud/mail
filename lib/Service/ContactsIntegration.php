<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Service;

use OCP\Contacts\IManager;
use OCP\IConfig;

class ContactsIntegration {

	/** @var IManager */
	private $contactsManager;

	/** @var IConfig */
	private $config;

	public function __construct(IManager $contactsManager, IConfig $config) {
		$this->contactsManager = $contactsManager;
		$this->config = $config;
	}

	/**
	 * Extracts all matching contacts with email address and name
	 *
	 * @param string $term
	 * @return array
	 */
	public function getMatchingRecipient(string $term): array {
		if (!$this->contactsManager->isEnabled()) {
			return [];
		}

		// If 'Allow username autocompletion in share dialog' is disabled in the admin sharing settings, then we must not
		// auto-complete system users
		$allowSystemUsers = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'no') === 'yes';

		$result = $this->contactsManager->search($term, ['FN', 'EMAIL']);
		$receivers = [];
		foreach ($result as $r) {
			if (!$allowSystemUsers && isset($r['isLocalSystemBook']) && $r['isLocalSystemBook']) {
				continue;
			}

			$id = $r['UID'];
			$fn = $r['FN'];
			if (!isset($r['EMAIL'])) {
				continue;
			}
			$email = $r['EMAIL'];
			if (!is_array($email)) {
				$email = [$email];
			}
			$photo = isset($r['PHOTO']) ? $this->getPhotoUri($r['PHOTO']) : null;

			// loop through all email addresses of this contact
			foreach ($email as $e) {
				if ($e === '') {
					continue;
				}
				$receivers[] = [
					'id' => $id,
					'label' => "$fn ($e)",
					'email' => $e,
					'photo' => $photo,
				];
			}
		}

		return $receivers;
	}

	/**
	 * @param string $email
	 * @return null|string
	 */
	public function getPhoto(string $email) {
		$result = $this->contactsManager->search($email, ['EMAIL']);
		if (count($result) > 0) {
			if (isset($result[0]['PHOTO'])) {
				return $this->getPhotoUri($result[0]['PHOTO']);
			}
		}
		return null;
	}

	/**
	 * @return false|null|string
	 */
	private function getPhotoUri(string $raw) {
		$uriPrefix = 'VALUE=uri:';
		if (substr($raw, 0, strlen($uriPrefix)) === $uriPrefix) {
			return substr($raw, strpos($raw, 'http'));
		} else {
			// ignore contacts >= 1.0 binary images
			// TODO: fix
			return null;
		}
	}
}
