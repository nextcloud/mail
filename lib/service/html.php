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
use HTMLPurifier_HTMLDefinition;
use HTMLPurifier_URISchemeRegistry;
use Kwi\UrlLinker;
use OCA\Mail\Service\HtmlPurify\CidURIScheme;
use OCA\Mail\Service\HtmlPurify\TransformNoReferrer;
use OCA\Mail\Service\HtmlPurify\TransformURLScheme;

class Html {

	/**
	 * @param string $data
	 * @return string
	 */
	public function convertLinks($data) {
		$linker = new UrlLinker(true, false);
		$data = $linker->linkUrlsInTrustedHtml($data);

		$config = HTMLPurifier_Config::createDefault();

		// Append target="_blank" to all link (a) elements
		$config->set('HTML.TargetBlank', true);

		// allow cid, http and ftp
		$config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'ftp' => true, 'mailto' => true]);

		// Disable the cache since ownCloud has no really appcache
		// TODO: Fix this - requires https://github.com/owncloud/core/issues/10767 to be fixed
		$config->set('Cache.DefinitionImpl', null);

		/** @var HTMLPurifier_HTMLDefinition $uri */
		$uri = $config->getDefinition('HTML');
		$uri->info_attr_transform_post['noreferrer'] = new TransformNoReferrer();

		$purifier = new HTMLPurifier($config);

		return $purifier->purify($data);
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
