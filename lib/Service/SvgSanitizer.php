<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Removes active content from SVG markup before it is embedded into or sent
 * with a message. SVGs are rendered in an <img>/CID context where scripts do
 * not execute, but they are still sanitised as defence in depth: any document
 * that cannot be parsed safely is dropped entirely.
 */
class SvgSanitizer {
	/** Elements that can carry or execute active content. */
	private const FORBIDDEN_ELEMENTS = [
		'script',
		'foreignObject',
		'handler',
		'listener',
		'set',
	];

	/**
	 * @param string $svg The raw (decoded) SVG markup
	 * @return string The sanitised markup, or an empty string if it cannot be
	 *                parsed safely
	 */
	public function sanitize(string $svg): string {
		if (trim($svg) === '') {
			return '';
		}

		// A DOCTYPE or entity declaration is not needed for plain SVG graphics
		// and is a common XXE / entity-expansion vector. Reject such documents.
		if (preg_match('/<!DOCTYPE|<!ENTITY/i', $svg) === 1) {
			return '';
		}

		$dom = new DOMDocument();
		$previousErrors = libxml_use_internal_errors(true);
		// LIBXML_NONET forbids any network access while parsing.
		$loaded = $dom->loadXML($svg, LIBXML_NONET);
		libxml_clear_errors();
		libxml_use_internal_errors($previousErrors);

		if (!$loaded || $dom->documentElement === null) {
			return '';
		}

		$xpath = new DOMXPath($dom);

		// Remove dangerous elements. Matching on the local name catches them
		// regardless of any namespace prefix (e.g. <x:script>).
		foreach (self::FORBIDDEN_ELEMENTS as $tag) {
			$nodes = $xpath->query('//*[local-name() = "' . $tag . '"]');
			if ($nodes !== false) {
				foreach (iterator_to_array($nodes) as $node) {
					$node->parentNode?->removeChild($node);
				}
			}
		}

		$elements = $xpath->query('//*');
		if ($elements !== false) {
			foreach ($elements as $element) {
				if ($element instanceof DOMElement) {
					$this->stripDangerousAttributes($element);
				}
			}
		}

		$result = $dom->saveXML($dom->documentElement);
		return $result === false ? '' : $result;
	}

	private function stripDangerousAttributes(DOMElement $element): void {
		/** @var DOMAttr $attribute */
		foreach (iterator_to_array($element->attributes) as $attribute) {
			$name = strtolower($attribute->nodeName);
			$value = trim($attribute->nodeValue ?? '');

			// Inline event handlers (onload, onclick, …).
			if (str_starts_with($name, 'on')) {
				$element->removeAttributeNode($attribute);
				continue;
			}

			// Only allow same-document references; strip javascript:, external
			// and data: URLs from links and resource references.
			if (in_array($name, ['href', 'xlink:href'], true) && !str_starts_with($value, '#')) {
				$element->removeAttributeNode($attribute);
			}
		}
	}
}
