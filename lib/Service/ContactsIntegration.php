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
use OCP\IGroupManager;
use OCP\IUserManager;
use function is_array;

class ContactsIntegration {
	/** @var IManager */
	private $contactsManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IConfig */
	private $config;

	public function __construct(IManager $contactsManager,
		IGroupManager $groupManager,
		IUserManager $userManager,
		IConfig $config) {
		$this->contactsManager = $contactsManager;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->config = $config;
	}

	/**
	 * Extracts all matching contacts with email address and name
	 *
	 * @param string $userId
	 * @param string $term
	 * @return array
	 */
	public function getMatchingRecipient(string $userId, string $term): array {
		$result = $this->search($userId, $term, ['UID', 'FN', 'EMAIL']);
		$receivers = [];
		foreach ($result as $r) {
			$id = $r['UID'];
			$fn = $r['FN'] ?? null;
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
					// Show full name if possible or fall back to email
					'label' => $fn ?? $e,
					'email' => $e,
					'photo' => $photo,
					'source' => 'contacts',
				];
			}
		}

		return $receivers;
	}

	/**
	 * @param string $email
	 *
	 * @return false|null|string
	 */
	public function getPhoto(string $email) {
		$result = $this->contactsManager->search($email, ['EMAIL']);
		foreach ($result as $contact) {
			if (!isset($contact['PHOTO']) || empty($contact['PHOTO'])) {
				continue;
			}
			return $this->getPhotoUri($contact['PHOTO']);
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

	/**
	 * Adds a new email to an existing Contact
	 *
	 * @param string $uid
	 * @param string $mailAddr
	 * @param string $type
	 * @return array|null
	 */
	public function addEmailToContact(string $uid, string $mailAddr, string $type = 'HOME') {
		if (!$this->contactsManager->isEnabled()) {
			return null;
		}

		$result = $this->contactsManager->search($uid, ['UID'], ['types' => true, 'limit' => 1]);

		if (count($result) !== 1) {
			return null; // no match
		}

		$newEntry = [
			'type' => $type,
			'value' => $mailAddr
		];

		$match = $result[0];
		$email = $match['EMAIL'] ?? [];
		if (!empty($email) && !is_array($email[0])) {
			$email = [$email];
		}
		$email[] = $newEntry;
		$match['EMAIL'] = $email;

		$updatedContact = $this->contactsManager->createOrUpdate($match, $match['addressbook-key']);
		return $updatedContact;
	}

	/**
	 * Adds a new contact with the specified email to an addressbook
	 */
	public function newContact(string $name, string $mailAddr, string $type = 'HOME', ?string $addressbook = null): ?array {
		if (!$this->contactsManager->isEnabled()) {
			return null;
		}

		if (!isset($addressbook)) {
			$addressbook = key($this->contactsManager->getUserAddressBooks());
		}

		$contact = [
			'FN' => $name,
			'EMAIL' => [
				[
					'type' => $type,
					'value' => $mailAddr
				]
			]
		];
		$createdContact = $this->contactsManager->createOrUpdate($contact, $addressbook);
		return $createdContact;
	}

	private function search(string $userId, string $term, array $fields, ?bool $strictSearch = null): array {
		if (!$this->contactsManager->isEnabled()) {
			return [];
		}

		// If 'Allow username autocompletion in share dialog' is disabled in the admin sharing settings, then we must not
		// auto-complete system users
		$shareeEnumeration = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
		$shareeEnumerationInGroupOnly = $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_group', 'no') === 'yes';
		$shareeEnumerationFullMatch = $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match', 'yes') === 'yes';
		$shareeEnumerationFullMatchDisplayName = $shareeEnumerationFullMatch && $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match_displayname', 'yes') === 'yes';
		$shareeEnumerationFullMatchUserId = $shareeEnumerationFullMatch && $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match_userid', 'yes') === 'yes';
		$shareeEnumerationFullMatchEmail = $shareeEnumerationFullMatch && $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match_email', 'yes') === 'yes';

		$options = [
			'enumeration' => $shareeEnumeration,
			'fullmatch' => $shareeEnumerationFullMatch,
			'limit' => 20,
		];
		if ($strictSearch !== null) {
			$options['strict_search'] = $strictSearch;
		}

		$result = $this->contactsManager->search(
			$term,
			$fields,
			$options,
		);

		$userGroups = [];
		if ($shareeEnumeration && $shareeEnumerationInGroupOnly) {
			$user = $this->userManager->get($userId);
			if ($user === null) {
				return [];
			}
			$userGroups = $this->groupManager->getUserGroupIds($user);
		}

		$filteredResults = [];
		foreach ($result as $r) {
			$isSystemUser = isset($r['isLocalSystemBook']) && $r['isLocalSystemBook'];
			$isInSameGroup = false;
			if ($isSystemUser && $shareeEnumerationInGroupOnly) {
				foreach ($userGroups as $userGroup) {
					if ($this->groupManager->isInGroup($r['UID'], $userGroup)) {
						$isInSameGroup = true;
						break;
					}
				}
				if (!$shareeEnumerationFullMatch && !$isInSameGroup) {
					continue;
				}
			}

			if ($isSystemUser && $shareeEnumerationInGroupOnly && !$isInSameGroup && $shareeEnumerationFullMatch) {
				// Check for full match. If full match is disabled, non-matching results already filtered out above.
				$id = $r['UID'];
				$fn = $r['FN'] ?? null;
				$lowerTerm = strtolower($term);
				$isMatch = ($lowerTerm !== '' && (
					($shareeEnumerationFullMatchDisplayName && !empty($fn) && $lowerTerm === strtolower($fn))
					|| ($shareeEnumerationFullMatchUserId && $lowerTerm === strtolower($id)))) ;
				if ($shareeEnumerationFullMatchEmail && !$isMatch) {
					$email = $r['EMAIL'] ?? null;
					if ($email === null) {
						continue;
					}
					$emails = is_array($email) ? $email : [$email];
					foreach ($emails as $e) {
						if ($lowerTerm === strtolower($e)) {
							$isMatch = true;
							break;
						}
					}
				}
				if (!$isMatch) {
					continue;
				}
			}

			$filteredResults[] = $r;
		}
		return $filteredResults;
	}

	/**
	 * @param string[] $fields
	 */
	private function doSearch(string $userId, string $term, array $fields, bool $strictSearch) : array {
		$result = $this->search($userId, $term, $fields, $strictSearch);
		$matches = [];
		foreach ($result as $r) {
			$id = $r['UID'];
			$fn = $r['FN'];
			$email = $r['EMAIL'] ?? null;
			$matches[] = [
				'id' => $id,
				'label' => $fn,
				'email' => $email,
			];
		}
		return $matches;
	}

	/**
	 * Extracts all Contacts with the specified mail address
	 */
	public function getContactsWithMail(string $userId, string $mailAddr): array {
		return $this->doSearch($userId, $mailAddr, ['EMAIL'], true);
	}

	/**
	 * Extracts all Contacts with the specified name
	 */
	public function getContactsWithName(string $userId, string $name): array {
		return $this->doSearch($userId, $name, ['FN'], false);
	}
}
