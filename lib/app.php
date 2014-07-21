<?php
/**
 * ownCloud - Mail app
 *
 * @author Thomas Müller
 * @copyright 2012 Thomas Müller thomas.mueller@tmit.eu
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
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail {

	class App
	{
		/**
		 * Extracts all matching contacts with email address and name
		 *
		 * @param $term
		 * @return array
		 */
		public static function getMatchingRecipient($term) {
			if (!\OCP\Contacts::isEnabled()) {
				return array();
			}

			$result = \OCP\Contacts::search($term, array('FN', 'EMAIL'));
			$receivers = array();
			foreach ($result as $r) {
				$id = $r['id'];
				$fn = $r['FN'];
				$email = $r['EMAIL'];
				if (!is_array($email)) {
					$email = array($email);
				}

				// loop through all email addresses of this contact
				foreach ($email as $e) {
					$displayName = "\"$fn\" <$e>";
					$receivers[] = array('id'    => $id,
										 'label' => $displayName,
										 'value' => $displayName);
				}
			}

			return $receivers;
		}

		public static function getPhoto($email) {
			$result = \OCP\Contacts::search($email, array('EMAIL'));
			if (count($result) > 0) {
				if (isset($result[0]['PHOTO'])) {
					$s = $result[0]['PHOTO'];
					return substr($s, strpos($s, 'http'));
				}
			}
			return \OCP\Util::imagePath('mail', 'person.png');
		}
	}
}
