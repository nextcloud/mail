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

namespace OCA\Mail\Service\HtmlPurify;

use HTMLPurifier_Config;
use HTMLPurifier_Context;
use HTMLPurifier_URI;
use HTMLPurifier_URIFilter;
use HTMLPurifier_URIParser;
use OCP\Util;

class TransformURLScheme extends HTMLPurifier_URIFilter {
	public $name = 'TransformURLScheme';
	public $post = true;

	/**
	 * @var \Closure
	 */
	private $mapCidToAttachmentId;

	public function __construct($messageParameters, \Closure $mapCidToAttachmentId) {
		$this->messageParameters = $messageParameters;
		$this->mapCidToAttachmentId = $mapCidToAttachmentId;
	}

	/**
	 * Transformator which will rewrite all HTTPS and HTTP urls to
	 * @param \HTMLPurifier_URI $uri
	 * @param HTMLPurifier_Config $config
	 * @param \HTMLPurifier_Context $context
	 * @return bool
	 */
	public function filter(&$uri, $config, $context) {
		/** @var \HTMLPurifier_Context $context */
		/** @var \HTMLPurifier_Config $config */

		// Only HTTPS and HTTP urls should get rewritten
		if ($uri->scheme === 'https' || $uri->scheme === 'http') {
			$uri = $this->filterHttp($uri, $context);
		}

		if ($uri->scheme === 'cid') {
			$attachmentId = $this->mapCidToAttachmentId->__invoke($uri->path);
			if (is_null($attachmentId)) {
				return true;
			}
			$this->messageParameters['attachmentId'] = $attachmentId;

			$imgUrl = \OC::$server->getURLGenerator()->linkToRouteAbsolute('mail.messages.downloadAttachment', $this->messageParameters);
			$parser = new HTMLPurifier_URIParser();
			$uri = $parser->parse($imgUrl);
		}

		return true;
	}

	/**
	 * @param $uri
	 * @param $context
	 * @return HTMLPurifier_URI
	 */
	private function filterHttp(&$uri, $context) {
		$originalURL = urlencode($uri->scheme . '://' . $uri->host . $uri->path);
		if ($uri->query !== null) {
			$originalURL = $originalURL . urlencode('?' . $uri->query);
		}

		// Get the HTML attribute
		$element = $context->get('CurrentAttr');

		// If element is of type "href" it is most likely a link that should get redirected
		// otherwise it's an element that we send through our proxy
		if ($element === 'href') {
			$uri = new \HTMLPurifier_URI(
				Util::getServerProtocol(),
				null,
				Util::getServerHost(),
				null,
				\OC::$server->getURLGenerator()->linkToRoute('mail.proxy.redirect'),
				'src=' . $originalURL,
				null);
			return $uri;
		} else {
			$uri = new \HTMLPurifier_URI(
				Util::getServerProtocol(),
				null,
				Util::getServerHost(),
				null,
				\OC::$server->getURLGenerator()->linkToRoute('mail.proxy.proxy'),
				'src=' . $originalURL . '&requesttoken=' . \OC::$server->getSession()->get('requesttoken'),
				null);
			return $uri;
		}
	}
}
