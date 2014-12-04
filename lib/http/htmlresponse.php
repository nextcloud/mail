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

namespace OCA\Mail\Http;

use OCP\AppFramework\Http\Response;

class HtmlResponse extends Response {

	private $content;

	public function __construct($content) {
		$this->content = $content;
	}

	/**
	 * Simply sets the headers and returns the file contents
	 * @return string the file contents
	 */
	public function render() {
		return $this->content;
	}

}
