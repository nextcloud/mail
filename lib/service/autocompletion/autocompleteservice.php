<?php

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

namespace OCA\Mail\Service\AutoCompletion;

use OCA\Mail\Service\ContactsIntegration;

class AutoCompleteService {

	/** @var ContactsIntegration */
	private $contactsIntegration;

	/** @var AddressCollector */
	private $addressCollector;

	public function __construct(ContactsIntegration $ci, AddressCollector $ac) {
		$this->contactsIntegration = $ci;
		$this->addressCollector = $ac;
	}

	public function findMathes($term) {
		$recipientsFromContacts = $this->contactsIntegration->getMatchingRecipient($term);
		$fromCollector = $this->addressCollector->searchAddress($term);

		// Convert collected addresses into same format as CI creates
		$recipientsFromCollector = array_map(function ($address) {
			return [
				'id' => $address->getId(),
				'label' => $address->getEmail(),
				'value' => $address->getEmail(),
			];
		}, $fromCollector);

		return array_merge($recipientsFromContacts, $recipientsFromCollector);
	}

}
