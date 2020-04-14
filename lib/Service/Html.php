<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jakob Sack <jakob@owncloud.org>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
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
use OCA\Mail\Service\HtmlPurify\TransformCSSBackground;
use OCA\Mail\Service\HtmlPurify\TransformHTMLLinks;
use OCA\Mail\Service\HtmlPurify\TransformImageSrc;
use OCA\Mail\Service\HtmlPurify\TransformNoReferrer;
use OCA\Mail\Service\HtmlPurify\TransformURLScheme;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Util;

require_once __DIR__ . '/../../vendor/cerdic/css-tidy/class.csstidy.php';

class Html {

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IRequest */
	private $request;

	public function __construct(IURLGenerator $urlGenerator, IRequest $request) {
		$this->urlGenerator = $urlGenerator;
		$this->request = $request;
	}

	/**
	 * @param string $data
	 * @return string
	 */
	public function convertLinks(string $data): string {
		$linker = new UrlLinker(true, false);
		$data = $linker->linkUrlsAndEscapeHtml($data);

		$config = HTMLPurifier_Config::createDefault();

		// Append target="_blank" to all link (a) elements
		$config->set('HTML.TargetBlank', true);

		// allow cid, http and ftp
		$config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'ftp' => true, 'mailto' => true]);
		$config->set('URI.Host', Util::getServerHostName());

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
	public function parseMailBody(string $body): array {
		$signature = null;
		$parts = explode("-- \r\n", $body);
		if (count($parts) > 1) {
			$signature = array_pop($parts);
			$body = implode("-- \r\n", $parts);
		}

		return [
			$body,
			$signature
		];
	}

	public function sanitizeHtmlMailBody(string $mailBody, array $messageParameters, Closure $mapCidToAttachmentId): string {
		$config = HTMLPurifier_Config::createDefault();

		// Append target="_blank" to all link (a) elements
		$config->set('HTML.TargetBlank', true);

		// allow cid, http and ftp
		$config->set('URI.AllowedSchemes', ['cid' => true, 'http' => true, 'https' => true, 'ftp' => true, 'mailto' => true]);
		$config->set('URI.Host', Util::getServerHostName());

		$config->set('Filter.ExtractStyleBlocks', true);

		// Disable the cache since ownCloud has no really appcache
		// TODO: Fix this - requires https://github.com/owncloud/core/issues/10767 to be fixed
		$config->set('Cache.DefinitionImpl', null);

		// Rewrite URL for redirection and proxying of content
		$html = $config->getDefinition('HTML');
		$html->info_attr_transform_post['imagesrc'] = new TransformImageSrc($this->urlGenerator);
		$html->info_attr_transform_post['cssbackground'] = new TransformCSSBackground($this->urlGenerator);
		$html->info_attr_transform_post['htmllinks'] = new TransformHTMLLinks();

		$uri = $config->getDefinition('URI');
		$uri->addFilter(new TransformURLScheme($messageParameters, $mapCidToAttachmentId, $this->urlGenerator, $this->request), $config);

		HTMLPurifier_URISchemeRegistry::instance()->register('cid', new CidURIScheme());


		$purifier = new HTMLPurifier($config);

		$result = $purifier->purify($mailBody);
		// eat xml parse errors within HTMLPurifier
		libxml_clear_errors();

		// Add back the style tag
		$styles = $purifier->context->get('StyleBlocks');
		if ($styles) {
			$result = implode("\n", [
				'<style type="text/css">',
				implode("\n", $styles),
				'</style>',
				$result,]);
		}
		return $result;
	}
}
