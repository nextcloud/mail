<?php

declare(strict_types=1);

/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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

use DOMDocument;
use DOMElement;
use DOMNode;
use Horde_Mime_Part;
use Horde_Text_Filter;
use OCA\Mail\Exception\InvalidDataUriException;
use OCA\Mail\Service\DataUri\DataUriParser;

class MimeMessage {
	private DataUriParser $uriParser;

	public function __construct(DataUriParser $uriParser) {
		$this->uriParser = $uriParser;
	}

	/**
	 * @param bool $isHtml
	 * @param string $content
	 * @param Horde_Mime_Part[] $attachments
	 * @return Horde_Mime_Part
	 */
	public function build(bool $isHtml, string $content, array $attachments): Horde_Mime_Part {
		if ($isHtml) {
			$imageParts = [];
			if (empty($content)) {
				$htmlContent = $textContent = $content;
			} else {
				$doc = new DOMDocument();
				$doc->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);

				$images = $doc->getElementsByTagName('img');

				for ($i = 0; $i < $images->count(); $i++) {
					$image = $images->item($i);
					if (!($image instanceof DOMElement)) {
						continue;
					}

					$src = $image->getAttribute('src');
					if ($src === '') {
						continue;
					}

					try {
						$dataUri = $this->uriParser->parse($src);
					} catch (InvalidDataUriException $e) {
						continue;
					}

					$part = new Horde_Mime_Part();
					$part->setType($dataUri->getMediaType());
					$part->setCharset($dataUri->getParameters()['charset']);
					$part->setName('embedded_image_' . $i);
					$part->setDisposition('inline');
					if ($dataUri->isBase64()) {
						$part->setTransferEncoding('base64');
					}
					$part->setContents($dataUri->getData());

					$cid = $part->setContentId();
					$imageParts[] = $part;

					$image->setAttribute('src', 'cid:' . $cid);
				}

				$htmlContent = $doc->saveHTML($doc->documentElement);
				$textContent = Horde_Text_Filter::filter($htmlContent, 'Html2text', ['callback' => [$this, 'htmlToTextCallback']]);
			}

			$alternativePart = new Horde_Mime_Part();
			$alternativePart->setType('multipart/alternative');

			$htmlPart = new Horde_Mime_Part();
			$htmlPart->setType('text/html');
			$htmlPart->setCharset('UTF-8');
			$htmlPart->setContents($htmlContent);
			$htmlPart->setDescription('HTML Version of Message');

			$textPart = new Horde_Mime_Part();
			$textPart->setType('text/plain');
			$textPart->setCharset('UTF-8');
			$textPart->setContents($textContent);
			$textPart->setDescription('Plaintext Version of Message');

			/*
			 * RFC1341: In general, user agents that compose multipart/alternative entities should place the
			 * body parts in increasing order of preference, that is, with the preferred format last.
			 */
			$alternativePart[] = $textPart;
			$alternativePart[] = $htmlPart;

			/*
			 * Wrap the multipart/alternative parts in multipart/related when inline images are found.
			 */
			if (count($imageParts) > 0) {
				$bodyPart = new Horde_Mime_Part();
				$bodyPart->setType('multipart/related');
				$bodyPart[] = $alternativePart;
				foreach ($imageParts as $imagePart) {
					$bodyPart[] = $imagePart;
				}
			} else {
				$bodyPart = $alternativePart;
			}
		} else {
			$bodyPart = new Horde_Mime_Part();
			$bodyPart->setType('text/plain');
			$bodyPart->setCharset('UTF-8');
			$bodyPart->setContents($content);
		}

		/*
		 * For attachments wrap the body (multipart/related, multipart/alternative or text/plain) in
		 * a multipart/mixed part.
		 */
		if (count($attachments) > 0) {
			$basePart = new Horde_Mime_Part();
			$basePart->setType('multipart/mixed');
			$basePart[] = $bodyPart;
			foreach ($attachments as $attachment) {
				$basePart[] = $attachment;
			}
		} else {
			$basePart = $bodyPart;
		}

		/*
		 * To add the Mime-Version-Header
		 */
		$basePart->isBasePart(true);

		return $basePart;
	}

	/**
	 * A callback for Horde_Text_Filter.
	 *
	 * The purpose of this callback is to overwrite the default behaviour
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
