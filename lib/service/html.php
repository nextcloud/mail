<?php
 /**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller deepdiver@owncloud.com
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Service;

class Html {

	public function __construct() {
		$config = \HTMLPurifier_Config::createDefault();
		$config->set('HTML.ForbiddenAttributes', 'class');
//		$config->set('Cache.SerializerPath', $directory);
//		$config->set('HTML.SafeIframe', true);
		$config->set('URI.SafeIframeRegexp',
			'%^(?:https?:)?//(' .
			'www.youtube(?:-nocookie)?.com/embed/|' .
			'player.vimeo.com/video/)%'); //allow YouTube and Vimeo
		$this->purifier = new \HTMLPurifier($config);
	}

	/**
	 * @param string $data
	 * @return string
	 */
	public function convertLinks($data) {
		$data = preg_replace("/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/",
			"<a href=\"\\0\" target=\"_blank\" rel=\"noreferrer\">\\0</a>", $data);
		return $data;
	}

	/**
	 * split off the signature
	 *
	 * @param string $body
	 * @return array
	 */
	public function parseMailBody($body) {
		$signature = null;
		$parts = explode("-- \r\n", $body);
		if (count($parts) > 1) {
			$signature = nl2br(array_pop($parts));
			$body = implode("-- \r\n", $parts);
		}

		return array($body, $signature);
	}

	public function sanitizeHtmlMailBody($mailBody) {
		return $this->purifier->purify($mailBody);
	}

}
