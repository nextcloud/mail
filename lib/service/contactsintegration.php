<?php
/**
 * ownCloud - Mail app
 *
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller thomas.mueller@tmit.eu
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Service {

	class ContactsIntegration
	{
		/**
		 * @var \OCP\Contacts\IManager
		 */
		private $contactsManager;

		public function __construct($contactsManager) {
			$this->contactsManager = $contactsManager;
		}
		/**
		 * Extracts all matching contacts with email address and name
		 *
		 * @param string $term
		 * @return array
		 */
		public function getMatchingRecipient($term) {
			if (!$this->contactsManager->isEnabled()) {
				return array();
			}

			$result = $this->contactsManager->search($term, array('FN', 'EMAIL'));
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

		/**
		 * @param string $email
		 * @return null|string
		 */
		public function getPhoto($email) {
			$result = $this->contactsManager->search($email, array('EMAIL'));
			if (count($result) > 0) {
				if (isset($result[0]['PHOTO'])) {
					$s = $result[0]['PHOTO'];
					return substr($s, strpos($s, 'http'));
				}
			}
			return null;
		}
	}
}
