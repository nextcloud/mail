<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service;

use DOMDocument;
use DOMElement;
use DOMNode;
use Horde_Mime_Part;
use Horde_Text_Filter;
use OCA\Mail\Exception\InvalidDataUriException;
use OCA\Mail\Html\Parser;
use OCA\Mail\Service\DataUri\DataUriParser;

class MimeMessage {
	private DataUriParser $uriParser;
	private SvgSanitizer $svgSanitizer;

	public function __construct(DataUriParser $uriParser, SvgSanitizer $svgSanitizer) {
		$this->uriParser = $uriParser;
		$this->svgSanitizer = $svgSanitizer;
	}

	/**
	 * Generates mime message
	 *
	 * @param list<Horde_Mime_Part> $attachments
	 *
	 * @return Horde_Mime_Part
	 */
	public function build(
		?string $contentPlain,
		?string $contentHtml,
		bool $isPgpEncrypted,
		array $attachments,
	): Horde_Mime_Part {
		[$inlineAttachments, $attachments] = $this->separateAttachments($attachments);

		if ($isPgpEncrypted === true && isset($contentPlain)) {
			$basePart = $this->buildPgpPart($contentPlain);
		} elseif (count($attachments) > 0) {
			/*
			* Messages with non embedded attachments need to be wrap in a multipart/mixed part
			*/
			$basePart = new Horde_Mime_Part();
			$basePart->setType('multipart/mixed');
			$basePart[] = $this->buildMessagePart($contentPlain, $contentHtml, $inlineAttachments);
			foreach ($attachments as $attachment) {
				$basePart[] = $attachment;
			}
		} else {
			$basePart = $this->buildMessagePart($contentPlain, $contentHtml, $inlineAttachments);
		}

		$basePart->isBasePart(true);

		return $basePart;
	}

	/**
	 * generates html/plain message part
	 *
	 * @param list<Horde_Mime_Part> $inlineAttachments
	 *
	 * @return Horde_Mime_Part
	 */
	private function buildMessagePart(?string $contentPlain, ?string $contentHtml, array $inlineAttachments): Horde_Mime_Part {

		if (isset($contentHtml)) {

			// determine if content is wrapped properly in a html tag, otherwise we need to wrap it properly
			if (mb_strpos($contentHtml, '<html') === false) {
				$source = '<!DOCTYPE html><html><meta http-equiv="content-type" content="text/html; charset=UTF-8"><body>' . PHP_EOL . $contentHtml . PHP_EOL . '</body>';
			} else {
				$source = ' ' . $contentHtml;
			}

			$doc = Parser::parseToDomDocument($source);
			array_push($inlineAttachments, ...$this->extractDataUriImages($doc));
			$this->rewriteSrcToCid($doc);
			$this->normalizeImageDimensions($doc);
			$this->normalizeImageAlignment($doc);
			$htmlContent = $doc->saveHTML();

			$htmlPart = new Horde_Mime_Part();
			$htmlPart->setType('text/html');
			$htmlPart->setCharset('UTF-8');
			$htmlPart->setContents($htmlContent);
		}

		if (isset($contentPlain)) {
			$plainPart = new Horde_Mime_Part();
			$plainPart->setType('text/plain');
			$plainPart->setCharset('UTF-8');
			$plainPart->setContents($contentPlain);
		} elseif (!isset($contentPlain) && isset($contentHtml)) {
			$plainPart = new Horde_Mime_Part();
			$plainPart->setType('text/plain');
			$plainPart->setCharset('UTF-8');
			$plainPart->setContents(
				Horde_Text_Filter::filter($contentHtml, 'Html2text', ['callback' => [$this, 'htmlToTextCallback']])
			);
		}

		if (isset($plainPart, $htmlPart)) {
			/*
			* RFC1341: Multipart/alternative entities should place the body parts in
			* increasing order of preference, that is, with the preferred format last.
			*/
			$messagePart = new Horde_Mime_Part();
			$messagePart->setType('multipart/alternative');
			$messagePart[] = $plainPart;
			$messagePart[] = $htmlPart;
		} elseif (isset($htmlPart)) {
			$messagePart = $htmlPart;
		} elseif (isset($plainPart)) {
			$messagePart = $plainPart;
		} else {
			$messagePart = new Horde_Mime_Part();
		}

		if (count($inlineAttachments) > 0) {
			/*
			 * Text parts with embedded content (e.g. inline images, etc) need be wrapped in multipart/related part
			 */
			$basePart = new Horde_Mime_Part();
			$basePart->setType('multipart/related');
			$basePart[] = $messagePart;
			foreach ($inlineAttachments as $part) {
				$basePart[] = $part;
			}
		} else {
			$basePart = $messagePart;
		}

		return $basePart;
	}

	/**
	 * Extracts data URI images from the HTML document, converts each to an
	 * inline MIME part with a generated Content-ID, and rewrites the src
	 * attribute to the corresponding cid: reference.
	 *
	 * @return Horde_Mime_Part[]
	 */
	private function extractDataUriImages(DOMDocument $doc): array {
		$parts = [];
		foreach ($doc->getElementsByTagName('img') as $id => $image) {
			if (!($image instanceof DOMElement)) {
				continue;
			}

			$src = $image->getAttribute('src');
			if ($src === '') {
				continue;
			}

			try {
				$dataUri = $this->uriParser->parse($src);
			} catch (InvalidDataUriException) {
				continue;
			}

			if (!str_starts_with($dataUri->getMediaType(), 'image/')) {
				continue;
			}

			$part = new Horde_Mime_Part();
			$part->setType($dataUri->getMediaType());
			$part->setCharset($dataUri->getParameters()['charset']);
			$part->setName('embedded_image_' . $id);
			$part->setDisposition('inline');
			if ($dataUri->getMediaType() === 'image/svg+xml') {
				// Strip any active content from SVGs before they are sent.
				$raw = $dataUri->isBase64()
					? base64_decode($dataUri->getData(), true)
					: rawurldecode($dataUri->getData());
				$part->setTransferEncoding('base64');
				$part->setContents(base64_encode($this->svgSanitizer->sanitize($raw === false ? '' : $raw)));
			} else {
				if ($dataUri->isBase64()) {
					$part->setTransferEncoding('base64');
				}
				$part->setContents($dataUri->getData());
			}

			$cid = $part->setContentId();
			$parts[] = $part;

			$image->setAttribute('src', 'cid:' . $cid);
		}
		return $parts;
	}

	/**
	 * Mirrors pixel dimensions set via the editor's resize feature (stored as
	 * inline CSS) onto the width/height attributes. Many email clients drop
	 * inline styles and classes but honour these attributes, so without them a
	 * resized image would fall back to its (often huge) natural size.
	 */
	private function normalizeImageDimensions(DOMDocument $doc): void {
		foreach ($doc->getElementsByTagName('img') as $image) {
			if (!($image instanceof DOMElement)) {
				continue;
			}

			// The editor stores the resize width as inline CSS. For inline images
			// it lives on the <img>; for block images it lives on the wrapping
			// <figure> while the aspect ratio stays on the <img>. A linked image
			// is nested one level deeper (<figure><a><img></a></figure>), so walk
			// up the ancestors to find the figure rather than only checking the
			// direct parent. Inspect both the image's and the figure's style.
			$figureStyle = '';
			$ancestor = $image->parentNode;
			while ($ancestor instanceof DOMElement) {
				if (strtolower($ancestor->tagName) === 'figure') {
					$figureStyle = $ancestor->getAttribute('style');
					break;
				}
				$ancestor = $ancestor->parentNode;
			}
			$style = $image->getAttribute('style') . ';' . $figureStyle;

			if (preg_match('/(?<![-\w])width:\s*([\d.]+)px/i', $style, $widthMatch) !== 1) {
				continue;
			}

			$width = (int)round((float)$widthMatch[1]);
			if ($width <= 0) {
				continue;
			}
			$image->setAttribute('width', (string)$width);

			// Derive the height from the aspect ratio the editor stores so clients
			// that ignore CSS keep the correct proportions.
			if (preg_match('#aspect-ratio:\s*([\d.]+)\s*/\s*([\d.]+)#i', $style, $ratioMatch) === 1) {
				$ratioWidth = (float)$ratioMatch[1];
				$ratioHeight = (float)$ratioMatch[2];
				if ($ratioWidth > 0.0 && $ratioHeight > 0.0) {
					$height = (int)round($width * $ratioHeight / $ratioWidth);
					if ($height > 0) {
						$image->setAttribute('height', (string)$height);
					}
				}
			}
		}
	}

	/**
	 * Translates the editor's image-alignment classes into an inline text-align
	 * style on the wrapping <figure>. Those classes rely on the editor's own
	 * stylesheet, which recipients do not load; aligning the (inline) image via
	 * text-align on its block wrapper is honoured by virtually every mail client.
	 * The unstyled, left-aligned default needs no rewriting.
	 */
	private function normalizeImageAlignment(DOMDocument $doc): void {
		$alignments = [
			'image-style-block-align-left' => 'left',
			'image-style-align-center' => 'center',
			'image-style-block-align-right' => 'right',
		];

		foreach ($doc->getElementsByTagName('figure') as $figure) {
			if (!($figure instanceof DOMElement)) {
				continue;
			}

			$classes = $figure->getAttribute('class');
			if ($classes === '') {
				continue;
			}

			foreach ($alignments as $class => $align) {
				if (!str_contains($classes, $class)) {
					continue;
				}

				$style = rtrim(trim($figure->getAttribute('style')), ';');
				$figure->setAttribute('style', ($style === '' ? '' : $style . '; ') . 'text-align: ' . $align);
				break;
			}
		}
	}

	/**
	 * Rewrites src attributes of img elements that carry a data-cid attribute
	 * back to their cid: reference, as required by the MIME structure for
	 * inline attachments when forwarding messages.
	 */
	private function rewriteSrcToCid(DOMDocument $doc): void {
		foreach ($doc->getElementsByTagName('img') as $image) {
			if (!($image instanceof DOMElement)) {
				continue;
			}

			$cid = $image->getAttribute('data-cid');
			if ($cid === '') {
				continue;
			}

			$image->setAttribute('src', 'cid:' . $cid);
			$image->removeAttribute('data-cid');
		}
	}

	/**
	 * generates pgp encrypted message part
	 *
	 * @param string $content
	 *
	 * @return Horde_Mime_Part
	 */
	private function buildPgpPart(string $content): Horde_Mime_Part {

		$contentPart = new Horde_Mime_Part();
		$contentPart->setType('application/octet-stream');
		$contentPart->setContentTypeParameter('name', 'encrypted.asc');
		$contentPart->setTransferEncoding('7bit');
		$contentPart->setDisposition('inline');
		$contentPart->setDispositionParameter('filename', 'encrypted.asc');
		$contentPart->setDescription('OpenPGP encrypted message');
		$contentPart->setContents($content);

		$pgpIdentPart = new Horde_Mime_Part();
		$pgpIdentPart->setType('application/pgp-encrypted');
		$pgpIdentPart->setTransferEncoding('7bit');
		$pgpIdentPart->setDescription('PGP/MIME Versions Identification');
		$pgpIdentPart->setContents('Version: 1');

		$basePart = new Horde_Mime_Part();
		$basePart->setType('multipart/encrypted');
		$basePart->setContentTypeParameter('protocol', 'application/pgp-encrypted');
		$basePart[] = $pgpIdentPart;
		$basePart[] = $contentPart;

		return $basePart;

	}

	/**
	 * @param list<Horde_Mime_Part> $attachments
	 * @return array{0: list<Horde_Mime_Part>, 1: list<Horde_Mime_Part>}
	 */
	private function separateAttachments(array $attachments): array {
		$inline = [];
		$normal = [];

		foreach ($attachments as $attachment) {
			if ($attachment->getDisposition() === 'inline') {
				$inline[] = $attachment;
			} else {
				$normal[] = $attachment;
			}
		}

		return [$inline, $normal];
	}

	/**
	 * A callback for Horde_Text_Filter.
	 *
	 * The purpose of this callback is to overwrite the default behavior
	 * of html2text filter to convert <p>Hello</p> => Hello\n\n with
	 * <p>Hello</p> => Hello\n.
	 *
	 * @param DOMDocument $doc
	 * @param DOMNode $node
	 * @return string|null non-null, add this text to the output and skip further processing of the node.
	 */
	public function htmlToTextCallback(DOMDocument $doc, DOMNode $node) {
		if ($node instanceof DOMElement && strtolower($node->tagName) === 'p') {
			return $node->textContent . "\n";
		}

		return null;
	}
}
