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
	 * Local names of attributes that carry URL references and must not point
	 * off-document. Matched without their namespace prefix because a reference
	 * can be bound to any prefix, not just the conventional xlink one.
	 */
	private const URL_ATTRIBUTES = ['href', 'src', 'action', 'formaction'];

	/** Encodings accepted for inspection; anything else is rejected outright. */
	private const ALLOWED_ENCODINGS = ['UTF-8', 'ISO-2022-JP', 'ISO-8859-1'];

	/** Reject payloads larger than this to prevent DoS via oversized documents. */
	private const MAX_SVG_BYTES = 2 * 1024 * 1024;

	/**
	 * @param string $svg The raw (decoded) SVG markup
	 * @return string The sanitised markup, or an empty string if it cannot be
	 *                parsed safely
	 */
	public function sanitize(string $svg): string {
		if (trim($svg) === '' || strlen($svg) > self::MAX_SVG_BYTES) {
			return '';
		}

		// The checks below compare plain strings, so they run on a normalised
		// copy of the payload: an exotic encoding or an embedded control
		// character would otherwise hide markup that the XML parser still sees.
		$inspectable = $this->normalize($svg);
		if ($inspectable === null) {
			return '';
		}

		// A DOCTYPE or entity declaration is not needed for plain SVG graphics
		// and is a common XXE / entity-expansion vector. Reject such documents.
		if (preg_match('/<!DOCTYPE|<!ENTITY/i', $inspectable) === 1) {
			return '';
		}

		// An XSL/Transform namespace signals a client-side transformation
		// stylesheet that can execute JavaScript in some browsers. Reject the
		// document outright, matching server-side hardening in nextcloud/server.
		if (str_contains($inspectable, 'http://www.w3.org/1999/XSL/Transform')) {
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

		// Remove processing instructions, e.g. an xml-stylesheet PI pointing at an
		// XSL sheet. Document-level PIs are already excluded by
		// saveXML($dom->documentElement), but PIs nested inside the root element
		// are handled here.
		$pis = $xpath->query('//processing-instruction()');
		if ($pis !== false) {
			foreach (iterator_to_array($pis) as $pi) {
				$pi->parentNode?->removeChild($pi);
			}
		}

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

		// Sanitise <style> element content: strip external CSS url() references.
		$styleNodes = $xpath->query('//*[local-name() = "style"]');
		if ($styleNodes !== false) {
			foreach ($styleNodes as $node) {
				$node->textContent = $this->stripCssUrls($node->textContent);
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

	/**
	 * Heuristically decide whether the given bytes are an SVG document.
	 */
	public function looksLikeSvg(string $content): bool {
		$start = ltrim($content);
		if (str_starts_with($start, "\xEF\xBB\xBF")) {
			$start = ltrim(substr($start, 3));
		}
		$hasSvgPrologue = str_starts_with($start, '<?xml')
			|| stripos($start, '<svg') === 0;
		return $hasSvgPrologue && stripos($content, '<svg') !== false;
	}

	/**
	 * Normalise the payload for the textual checks: decode it to UTF-8 and drop
	 * the control characters that a plain string comparison would trip over but
	 * the XML parser ignores. Mirrors the hardening of nextcloud/server#62162.
	 *
	 * @return string|null Null if the payload is not in an accepted encoding
	 */
	private function normalize(string $svg): ?string {
		$encoding = mb_detect_encoding($svg, self::ALLOWED_ENCODINGS, true);
		if ($encoding === false) {
			return null;
		}
		if ($encoding !== 'UTF-8') {
			$converted = mb_convert_encoding($svg, 'UTF-8', $encoding);
			if (!is_string($converted)) {
				return null;
			}
			$svg = $converted;
		}

		// Strip non-printable characters, but keep tab, newline and carriage
		// return as those are legal XML whitespace.
		return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $svg);
	}

	private function stripDangerousAttributes(DOMElement $element): void {
		/** @var DOMAttr $attribute */
		foreach (iterator_to_array($element->attributes) as $attribute) {
			$name = strtolower($attribute->nodeName);
			$localName = $this->stripNamespacePrefix($name);
			$value = trim($attribute->nodeValue ?? '');

			// Inline event handlers (onload, onclick, …).
			if (str_starts_with($localName, 'on')) {
				$element->removeAttributeNode($attribute);
				continue;
			}

			// Only allow same-document references; strip javascript:, external
			// and data: URLs from links and resource references.
			if (in_array($localName, self::URL_ATTRIBUTES, true) && !str_starts_with($value, '#')) {
				$element->removeAttributeNode($attribute);
				continue;
			}

			// Strip external CSS url() references from inline style attributes.
			if ($name === 'style') {
				$element->setAttribute('style', $this->stripCssUrls($value));
			}
		}
	}

	/**
	 * Drop the namespace prefix of an attribute name. A prefix can be bound to
	 * any name (xlink:href, foo:href, …) and libxml keeps unresolved prefixes as
	 * part of the node name, so matching has to happen on the local name.
	 */
	private function stripNamespacePrefix(string $name): string {
		$separator = strrpos($name, ':');
		return $separator === false ? $name : substr($name, $separator + 1);
	}

	/**
	 * Replace CSS url() references that point outside the document with 'none'.
	 * Fragment references (url(#…)) are preserved for gradients and masks.
	 */
	private function stripCssUrls(string $css): string {
		$css = preg_replace('/@import[^;]*;?/i', '', $css) ?? $css;
		return preg_replace('/url\s*\((?!\s*[\'\"]?#)[^)]*\)/i', 'none', $css) ?? $css;
	}
}
