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

use Closure;
use HTMLPurifier;
use HTMLPurifier_Config;
use HTMLPurifier_URISchemeRegistry;
use OCA\Mail\Service\HtmlPurify\CidURIScheme;
use OCA\Mail\Service\HtmlPurify\TransformURLScheme;

class Html {

	/**
	 * @param string $data
	 * @return string
	 */
	public function convertLinks($data) {
		$regex = "/(ht|f)tp(s?)\:\/\/[a-zA-Z0-9\-\._]+(\:[0-9]+)?(\/[^!*';\"@&+$,?#\ \]]*)*(\?[^!*';:\=\"@&+$,?#\ \]]+=[^!*';:\"\=@&$?#\ \]()]+(&[^!*';:\=@&+$,?#\ \]]+=[^!*';:\"\=@&$?#\ \]()]*)*)?(\#\!?[^!*';\"@&$,?#\ \]]*)?/";
		$data = preg_replace($regex, "<a href=\"\\0\" target=\"_blank\" rel=\"noreferrer\">\\0</a>", $data);
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

		return [
			$body,
			$signature
		];
	}

	public function sanitizeHtmlMailBody($mailBody, array $messageParameters, Closure $mapCidToAttachmentId) {
		$config = HTMLPurifier_Config::createDefault();

		// Append target="_blank" to all link (a) elements
		$config->set('HTML.TargetBlank', true);

		// allow cid, http and ftp
		$config->set('URI.AllowedSchemes', ['cid' => true, 'http' => true, 'https' => true, 'ftp' => true, 'mailto' => true]);

		// Disable the cache since ownCloud has no really appcache
		// TODO: Fix this - requires https://github.com/owncloud/core/issues/10767 to be fixed
		$config->set('Cache.DefinitionImpl', null);

		// Rewrite URL for redirection and proxying of content
		$uri = $config->getDefinition('URI');
		$uri->addFilter(new TransformURLScheme($messageParameters, $mapCidToAttachmentId), $config);

		HTMLPurifier_URISchemeRegistry::instance()->register('cid', new CidURIScheme());

		$purifier = new HTMLPurifier($config);

		return $purifier->purify($mailBody);
	}

}
