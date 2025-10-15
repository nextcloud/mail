<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\PhishingDetection;

use OCA\Mail\Html\Parser;
use OCA\Mail\PhishingDetectionResult;
use OCP\IL10N;
use URL\Normalizer;

class LinkCheck {
	protected IL10N $l10n;


	public function __construct(IL10N $l10n) {
		$this->l10n = $l10n;
	}
	// checks if link text is meant to look like a link
	private function textLooksLikeALink(string $text): bool {
		// based on https://gist.github.com/gruber/8891611
		$pattern = '/(?i)\b((?:https?:(?:\/{1,3}|[a-z0-9%])|[a-z0-9.\-]+[.](?:com|net|org|edu|gov|mil|aero|asia|biz|cat|coop|info|int|jobs|mobi|museum|name|post|pro|tel|travel|xxx|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cs|cu|cv|cx|cy|cz|dd|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|Ja|sk|sl|sm|sn|so|sr|ss|st|su|sv|sx|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)\/)(?:[^\s()<>{}\[\]]+|\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|\([^\s]+?\))+(?:\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|\([^\s]+?\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’])|(?:(?<!@)[a-z0-9]+(?:[.\-][a-z0-9]+)*[.](?:com|net|org|edu|gov|mil|aero|asia|biz|cat|coop|info|int|jobs|mobi|museum|name|post|pro|tel|travel|xxx|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cs|cu|cv|cx|cy|cz|dd|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|Ja|sk|sl|sm|sn|so|sr|ss|st|su|sv|sx|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)\b\/?(?!@)))/';

		return preg_match($pattern, $text) === 1;
	}

	private function getInnerText(\DOMElement $node) : string {
		$innerText = '';
		foreach ($node->childNodes as $child) {
			if ($child->nodeType === XML_TEXT_NODE) {
				$innerText .= $child->nodeValue;
			} elseif ($child->nodeType === XML_ELEMENT_NODE) {
				$innerText .= $this->getInnerText($child);
			}
		}
		return $innerText;
	}

	private function parse(string $url): string {
		if (!str_contains($url, '://')) {
			return 'http://' . $url;
		}
		return $url;
	}

	public function run(string $htmlMessage) : PhishingDetectionResult {

		$results = [];
		$zippedArray = [];

		$dom = Parser::parseToDomDocument($htmlMessage);
		$anchors = $dom->getElementsByTagName('a');
		foreach ($anchors as $anchor) {
			$href = $anchor->getAttribute('href');
			$linkText = $this->getInnerText($anchor);
			if ($href === '' || $linkText === '') {
				continue;
			}

			// Handle links that are wrapped in brackets, quotes, etc.
			// Need to use preg_match with the u(nicode) flag to properly match multibyte chars.
			if (preg_match('/^(?![[:alnum:]]).*(?![[:alnum:]])$/u', $linkText)) {
				$linkText = mb_substr($linkText, 1, -1);
			}

			$zippedArray[] = [
				'href' => $href,
				'linkText' => $linkText
			];
		}
		foreach ($zippedArray as $zipped) {
			$un = new Normalizer($zipped['href']);
			$url = $un->normalize();
			if ($this->textLooksLikeALink($zipped['linkText'])) {
				if (parse_url($this->parse($url), PHP_URL_HOST) !== parse_url($this->parse($zipped['linkText']), PHP_URL_HOST)) {
					$results[] = [
						'href' => $url,
						'linkText' => $zipped['linkText'],
					];
				}
			}
		}
		if (count($results) > 0) {
			return new PhishingDetectionResult(PhishingDetectionResult::LINK_CHECK, true, $this->l10n->t('Some addresses in this message are not matching the link text'), $results);
		}
		return  new PhishingDetectionResult(PhishingDetectionResult::LINK_CHECK, false);

	}

}
