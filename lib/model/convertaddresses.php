<?php

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

namespace OCA\Mail\Model;

use Horde_Imap_Client_Data_Envelope;
use Horde_Mail_Rfc822_List;
use Horde_Mail_Rfc822_Address;

trait ConvertAddresses {

	private function hordeToString(Horde_Mail_Rfc822_Address $address) {
		return $address->writeAddress();
	}

	private function hordeToAssoc(Horde_Mail_Rfc822_Address $address) {
		return [
			'label' => $address->label,
			'email' => $address->bare_address,
		];
	}

	/**
	 * Convert horde mail address list to array of strings
	 *
	 * @param Horde_Mail_Rfc822_List $list
	 * @return string[]
	 */
	protected function hordeListToStringArray(Horde_Mail_Rfc822_List $list) {
		$addresses = [];
		foreach ($list as $address) {
			$addresses[] = $this->hordeToString($address);
		}
		return $addresses;
	}

	/**
	 * @param Horde_Mail_Rfc822_List $list
	 * @return array
	 */
	protected function hordeListToAssocArray(Horde_Mail_Rfc822_List $list) {
		$addresses = [];
		foreach ($list as $address) {
			$addresses[] = $this->hordeToAssoc($address);
		}
		return $addresses;
	}

	/**
	 * @param Horde_Imap_Client_Data_Envelope|Horde_Mail_Rfc822_List $envelope
	 * @return array
	 */
	protected function convertAddressList($envelope) {
		$list = [];
		foreach ($envelope as $t) {
			$list[] = $this->hordeToAssoc($t);
		}
		return $list;
	}

}
