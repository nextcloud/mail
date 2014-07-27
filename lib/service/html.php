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

use HTMLPurifier_Config;
use HTMLPurifier_AttrTransform;
use HTMLPurifier_URIFilter;
use OCP\Util;
use OC_Helper;

class HTMLPurifier_AttrTransform_TargetAttribute extends HTMLPurifier_AttrTransform {
	public function transform($attr, $config, $context) {
		$attr['target'] = '_top';
		return $attr;
	}
}


class HTMLPurifier_URIFilter_TransformURLScheme extends HTMLPurifier_URIFilter
{
	public $name = 'TransformURLScheme';
	public $post = true;

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
		if ($uri->scheme !== 'https' && $uri->scheme !== 'http') {
			return true;
		}

		$originalURL = urlencode($uri->scheme.'://'.$uri->host.$uri->path);
		if($uri->query !== null) {
			$originalURL = $originalURL.urlencode('?'.$uri->query);
		}

		// Get the HTML attribute
		$element = $context->get('CurrentAttr');

		// If element is of type "href" it is most likely a link that should get redirected
		// otherwise it's an element that we send through our proxy
		if($element === 'href') {
			$uri = new \HTMLPurifier_URI(
				Util::getServerProtocol(),
				null,
				Util::getServerHost(),
				null,
				OC_Helper::linkToRoute( 'mail.proxy.redirect' ),
				'src='.$originalURL,
				null);
		} else {
			$uri = new \HTMLPurifier_URI(
				Util::getServerProtocol(),
				null,
				Util::getServerHost(),
				null,
				OC_Helper::linkToRoute( 'mail.proxy.proxy' ),
				'src='.$originalURL.'&requesttoken='.\OC::$session->get('requesttoken'),
				null);
		}

		return true;
	}
}


class Html {

	public function __construct() {
		$config = HTMLPurifier_Config::createDefault();

		// Append target="_parent" to all link (a) elements since it is iframed
		$htmlDef = $config->getHTMLDefinition(true);
		$link = $htmlDef->addBlankElement('a');
		$link->attr_transform_post[] = new HTMLPurifier_AttrTransform_TargetAttribute();

		// Rewrite URL for redirection and proxying of content
		$uri = $config->getDefinition('URI');
		$uri->addFilter(new HTMLPurifier_URIFilter_TransformURLScheme(), $config);

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
