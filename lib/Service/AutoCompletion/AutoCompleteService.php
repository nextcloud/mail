<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\AutoCompletion;

use OCA\Mail\Db\CollectedAddress;
use OCA\Mail\Service\ContactsIntegration;
use OCA\Mail\Service\GroupsIntegration;

class AutoCompleteService {
	/** @var ContactsIntegration */
	private $contactsIntegration;

	/** @var GroupsIntegration */
	private $groupsIntegration;

	/** @var AddressCollector */
	private $addressCollector;

	public function __construct(ContactsIntegration $ci, GroupsIntegration $gi, AddressCollector $ac) {
		$this->contactsIntegration = $ci;
		$this->groupsIntegration = $gi;
		$this->addressCollector = $ac;
	}

	public function findMatches(string $userId, string $term): array {
		$recipientsFromContacts = $this->contactsIntegration->getMatchingRecipient($userId, $term);
		$recipientGroups = $this->groupsIntegration->getMatchingGroups($term);
		$fromCollector = $this->addressCollector->searchAddress($userId, $term);

		// Convert collected addresses into same format as CI creates
		$recipientsFromCollector = array_map(static function (CollectedAddress $address) {
			return [
				'id' => $address->getId(),
				'label' => $address->getDisplayName(),
				'email' => $address->getEmail(),
				'source' => 'collector',
			];
		}, $fromCollector);

		return array_merge($recipientsFromContacts, $recipientsFromCollector, $recipientGroups);
	}
}
