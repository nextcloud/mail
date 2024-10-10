<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service;

use Closure;
use HTMLPurifier;
use HTMLPurifier_Config;
use HTMLPurifier_HTMLDefinition;
use HTMLPurifier_URIDefinition;
use HTMLPurifier_URISchemeRegistry;
use OCA\Mail\Service\HtmlPurify\CidURIScheme;
use OCA\Mail\Service\HtmlPurify\TransformHTMLLinks;
use OCA\Mail\Service\HtmlPurify\TransformImageSrc;
use OCA\Mail\Service\HtmlPurify\TransformNoReferrer;
use OCA\Mail\Service\HtmlPurify\TransformStyleURLs;
use OCA\Mail\Service\HtmlPurify\TransformURLScheme;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Util;
use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\Value\CSSString;
use Sabberworm\CSS\Value\URL;
use Youthweb\UrlLinker\UrlLinker;

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
		$linker = new UrlLinker([
			'allowFtpAddresses' => true,
			'allowUpperCaseUrlSchemes' => false,
			'htmlLinkCreator' => static function ($url) {
				// Render full url for the link description. Otherwise, potentially malicious query
				// params might be hidden.
				return sprintf('<a href="%1$s">%1$s</a>', htmlspecialchars($url));
			},
		]);
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
		$parts = preg_split("/-- (\n|(\r\n))/", $body);
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
		$config->set('Filter.ExtractStyleBlocks.TidyImpl', false);
		$config->set('CSS.AllowTricky', true);
		$config->set('CSS.Proprietary', true);

		// Disable the cache since ownCloud has no really appcache
		// TODO: Fix this - requires https://github.com/owncloud/core/issues/10767 to be fixed
		$config->set('Cache.DefinitionImpl', null);

		// Rewrite URL for redirection and proxying of content
		/** @var HTMLPurifier_HTMLDefinition $html */
		$html = $config->getDefinition('HTML');
		$html->info_attr_transform_post['imagesrc'] = new TransformImageSrc($this->urlGenerator);
		$html->info_attr_transform_post['cssbackground'] = new TransformStyleURLs($this->urlGenerator);
		$html->info_attr_transform_post['htmllinks'] = new TransformHTMLLinks($this->urlGenerator);

		/** @var HTMLPurifier_URIDefinition $uri */
		$uri = $config->getDefinition('URI');
		$uri->addFilter(new TransformURLScheme($messageParameters, $mapCidToAttachmentId, $this->urlGenerator, $this->request), $config);

		$uriSchemeRegistry = HTMLPurifier_URISchemeRegistry::instance();
		$uriSchemeRegistry->register('cid', new CidURIScheme());

		$uriSchemaData = new \HTMLPurifier_URIScheme_data();
		$uriSchemaData->allowed_types['image/bmp'] = true;
		$uriSchemaData->allowed_types['image/tiff'] = true;
		$uriSchemaData->allowed_types['image/webp'] = true;
		$uriSchemeRegistry->register('data', $uriSchemaData);

		$purifier = new HTMLPurifier($config);

		$result = $purifier->purify($mailBody);
		// eat xml parse errors within HTMLPurifier
		libxml_clear_errors();

		// Sanitize CSS rules
		$styles = $purifier->context->get('StyleBlocks');
		if ($styles) {
			$joinedStyles = implode("\n", $styles);
			$result = $this->sanitizeStyleSheet($joinedStyles) . $result;
		}
		return $result;
	}

	/**
	 * Block all URLs in the given CSS style sheet and return a formatted html style tag.
	 *
	 * @param string $styles The CSS style sheet to sanitize.
	 * @return string Rendered style tag to be used in a html response.
	 */
	public function sanitizeStyleSheet(string $styles): string {
		$cssParser = new Parser($styles);
		$css = $cssParser->parse();

		// Replace urls with blocked image
		$blockedUrl = new CSSString($this->urlGenerator->imagePath('mail', 'blocked-image.png'));
		$hasBlockedContent = false;
		foreach ($css->getAllValues() as $value) {
			if ($value instanceof URL) {
				$value->setURL($blockedUrl);
				$hasBlockedContent = true;
			}
		}

		// Save original styles to be able to restore them later
		$savedStyles = '';
		if ($hasBlockedContent) {
			$savedStyles = 'data-original-content="' . htmlspecialchars($styles) . '"';
			$styles = $css->render(OutputFormat::createCompact());
		}

		// Render style tag
		return implode('', [
			'<style type="text/css" ', $savedStyles, '>',
			$styles,
			'</style>',
		]);
	}
}
