<?php
 /**
 * ownCloud - Mail
 *
 * @author Jakob Sack
 * @copyright 2015 Jakob Sack jakob@owncloud.org
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Service\HtmlPurify;
use HTMLPurifier_AttrTransform;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use HTMLPurifier_URIParser;

/**
 * Adds target="_blank" to all outbound links.
 */
class TransformHTMLLinks extends HTMLPurifier_AttrTransform
{
    /**
     * @type HTMLPurifier_URIParser
     */
    private $parser;

    public function __construct() {
        $this->parser = new HTMLPurifier_URIParser();
    }

    /**
     * @param array $attr
     * @param HTMLPurifier_Config $config
     * @param HTMLPurifier_Context $context
     * @return array
     */
    public function transform($attr, $config, $context)
    {
        if (!isset($attr['href'])) {
            return $attr;
        }

        // XXX Kind of inefficient
        $attr['target'] = '_blank';

        return $attr;
    }
}
