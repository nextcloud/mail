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
use HTMLPurifier_URIScheme;

class CidURIScheme extends HTMLPurifier_URIScheme {

	public $default_port = null;
	public $browsable = true;
	public $hierarchical = true;

	public function validate(&$uri, $config, $context) {
		return true;
	}

	/**
	 * Validates the components of a URI for a specific scheme.
	 *
	 * @param HTMLPurifier_URI $uri Reference to a HTMLPurifier_URI object
	 * @param HTMLPurifier_Config $config
	 * @param HTMLPurifier_Context $context
	 * @return bool success or failure
	 */
	public function doValidate(&$uri, $config, $context) {
		return true;
	}
}
