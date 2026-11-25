<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service;

use DOMElement;
use DOMXPath;
use HTMLPurifier;
use HTMLPurifier_Config;
use HTMLPurifier_HTMLDefinition;
use HTMLPurifier_URIDefinition;
use HTMLPurifier_URISchemeRegistry;
use OCA\Mail\Html\Parser as HtmlParser;
use OCA\Mail\Html\ProxyHmacGenerator;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\HtmlPurify\CidURIScheme;
use OCA\Mail\Service\HtmlPurify\TransformCidDataAttr;
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

/**
 * @psalm-import-type IMAPAttachment from IMAPMessage
 */
class Html {
	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IRequest */
	private $request;

	public function __construct(
		IURLGenerator $urlGenerator,
		IRequest $request,
		private ProxyHmacGenerator $hmacGenerator,
	) {
		$this->urlGenerator = $urlGenerator;
		$this->request = $request;
	}

	/**
	 * @param string $data
	 * @return string
	 */
	public function convertLinks(string $data): string {
		if (!mb_check_encoding($data, 'UTF-8')) {
			// Some senders (e.g. Lotus Notes/Domino) declare a message as UTF-8 while
			// actually sending a different charset. UrlLinker's escapeHtml() calls
			// htmlspecialchars() without ENT_SUBSTITUTE/ENT_IGNORE, which returns an
			// empty string for the whole input on the first invalid byte it hits.
			$data = mb_scrub($data, 'UTF-8');
		}

		$linker = new UrlLinker([
			'allowFtpAddresses' => true,
			'allowUpperCaseUrlSchemes' => false,
			'skipAmbiguousTlds' => true,
			'htmlLinkCreator' => static fn ($url)
				// Render full url for the link description. Otherwise, potentially malicious query
				// params might be hidden.
				=> sprintf('<a href="%1$s">%1$s</a>', htmlspecialchars($url)),
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

		/** @var HTMLPurifier_HTMLDefinition $def */
		$def = $config->getHTMLDefinition(true);
		$def->info_attr_transform_post['noreferrer'] = new TransformNoReferrer();

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
		$parts = preg_split("/^-- \r?\n/m", $body);
		if ($parts === false) {
			$parts = [$body];
		}
		if (count($parts) > 1) {
			$signature = array_pop($parts);
			$body = implode("-- \r\n", $parts);
		}

		return [
			$body,
			$signature
		];
	}

	/**
	 * @param list<IMAPAttachment> $inlineAttachments
	 * @return list<array{id: string|null, messageId: int, fileName: string|null, mime: string, size: int, cid: string|null, disposition: string, url: string}>
	 */
	private function addAttachmentUrl(int $messageId, array $inlineAttachments): array {
		return array_map(function (array $inlineAttachment) use ($messageId) {
			$inlineAttachment['url'] = $this->urlGenerator->linkToRouteAbsolute(
				'mail.messages.downloadAttachment', [
					'id' => $messageId,
					'attachmentId' => $inlineAttachment['id']
				]
			);
			return $inlineAttachment;
		}, $inlineAttachments);
	}

	/**
	 * @param list<IMAPAttachment> $inlineAttachments
	 */
	public function sanitizeHtmlMailBody(int $messageId, string $mailBody, array $inlineAttachments): string {
		$inlineAttachments = $this->addAttachmentUrl($messageId, $inlineAttachments);

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
		$config->set('Attr.EnableID', true);

		// Disable the cache since ownCloud has no really appcache
		// TODO: Fix this - requires https://github.com/owncloud/core/issues/10767 to be fixed
		$config->set('Cache.DefinitionImpl', null);

		// Rewrite URL for redirection and proxying of content
		/** @var HTMLPurifier_HTMLDefinition $def */
		$def = $config->getHTMLDefinition(true);

		// HTMLPurifier only knows XHTML 1.0, which predates these
		$def->addElement('figure', 'Block', 'Flow', 'Common');
		$def->addElement('figcaption', 'Block', 'Flow', 'Common');

		$def->info_attr_transform_post['imagesrc'] = new TransformImageSrc($this->urlGenerator);
		$def->info_attr_transform_post['cssbackground'] = new TransformStyleURLs($this->urlGenerator);
		$def->info_attr_transform_post['htmllinks'] = new TransformHTMLLinks();
		if (count($inlineAttachments) > 0) {
			$def->info_attr_transform_post['datacid'] = new TransformCidDataAttr($inlineAttachments);
		}

		/** @var HTMLPurifier_URIDefinition $uri */
		$uri = $config->getURIDefinition(true);
		$uri->addFilter(
			new TransformURLScheme(
				$messageId,
				$inlineAttachments,
				$this->urlGenerator,
				$this->request,
				$this->hmacGenerator,
			),
			$config
		);

		$uriSchemeRegistry = HTMLPurifier_URISchemeRegistry::instance();
		$uriSchemeRegistry->register('cid', new CidURIScheme());

		$uriSchemaData = new \HTMLPurifier_URIScheme_data();
		$uriSchemaData->allowed_types['image/bmp'] = true;
		$uriSchemaData->allowed_types['image/tiff'] = true;
		$uriSchemaData->allowed_types['image/webp'] = true;
		$uriSchemeRegistry->register('data', $uriSchemaData);

		$purifier = new HTMLPurifier($config);

		// Downlevel-revealed conditionals are not comments, so no HTMLPurifier comment
		// setting covers them and some libxml versions leave them as visible text.
		// Keep the original on a PCRE error; purify(null) would silently return an empty body.
		$mailBody = preg_replace('/<!\[\s*(?:end)?if\b[^\]]*\]\s*>/i', '', $mailBody) ?? $mailBody;

		// HTMLPurifier marks a nested link as excluded and drops it together with
		// everything inside it, which deletes the visible text of the marker. Browsers
		// keep the inner link instead, so reduce nested links to that same shape before
		// purifying. The body is left untouched unless there is a nested link.
		$mailBody = $this->unwrapNestedLinks($mailBody);

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
	 * Reduce nested links to the shape browsers build from the same markup.
	 *
	 * HTML does not allow a link inside a link, and parsers disagree on how to
	 * recover. Browsers close the outer link and keep the inner one, so the text
	 * stays visible. HTMLPurifier instead marks the inner link as excluded and
	 * removes it with all of its content, which hides text like a verification
	 * code that a transactional mail wrapped in a nested anchor.
	 *
	 * @param string $html The HTML body to normalize.
	 * @return string The body with outer links unwrapped, or $html when it has none.
	 */
	private function unwrapNestedLinks(string $html): string {
		// Most bodies have no nested link at all; skip the DOM round trip for them.
		// The empty check also narrows the type for the parser below.
		if ($html === ''
			|| stripos($html, '</a') === false
			|| preg_match('/<a\b[^>]*>(?:(?!<\/a).)*<a\b/is', $html) !== 1) {
			return $html;
		}

		$document = HtmlParser::parseToDomDocument($html);
		$xpath = new DOMXPath($document);
		$outerLinks = $xpath->query('//a[.//a]');
		if ($outerLinks === false || $outerLinks->length === 0) {
			return $html;
		}

		/** @var DOMElement $outerLink */
		foreach ($outerLinks as $outerLink) {
			$parent = $outerLink->parentNode;
			if ($parent === null) {
				continue;
			}
			while ($outerLink->firstChild !== null) {
				$parent->insertBefore($outerLink->firstChild, $outerLink);
			}
			$parent->removeChild($outerLink);
		}

		// serialize the whole document: saveHTML() on a fragment drops the doctype
		return rtrim((string)$document->saveHTML(), "\n");
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
