<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\HtmlPurify;

use HTMLPurifier_AttrTransform;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use HTMLPurifier_URIParser;

/**
 * Sets data-cid on inline image elements after their cid: src has been replaced
 * with an attachment URL, so the MIME builder can reconstruct inline attachments
 * when the message is forwarded.
 */
class TransformCidDataAttr extends HTMLPurifier_AttrTransform {
	private HTMLPurifier_URIParser $parser;
	/** @var array<string, string> path => cid */
	private array $pathToCid;

	public function __construct(array $inlineAttachments) {
		$this->parser = new HTMLPurifier_URIParser();
		$this->pathToCid = [];
		foreach ($inlineAttachments as $inlineAttachment) {
			if (isset($inlineAttachment['cid'], $inlineAttachment['url'])) {
				$url = $this->parser->parse($inlineAttachment['url']);
				$this->pathToCid[$url->path] = $inlineAttachment['cid'];
			}
		}
	}

	/**
	 * @param array $attr
	 * @param HTMLPurifier_Config $config
	 * @param HTMLPurifier_Context $context
	 * @return array
	 */
	#[\Override]
	public function transform($attr, $config, $context) {
		if ($context->get('CurrentToken')->name !== 'img' || !isset($attr['src'])) {
			return $attr;
		}

		$url = $this->parser->parse($attr['src']);
		$cid = $this->pathToCid[$url->path] ?? null;

		if ($cid !== null) {
			$attr['data-cid'] = $cid;
		}

		return $attr;
	}
}
