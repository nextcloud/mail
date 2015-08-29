<?php

namespace OCA\Mail\Service\HtmlPurify;
use HTMLPurifier_AttrTransform;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use OCP\Util;

/**
 * Adds copies src to data-src on all img tags.
 */
class TransformImageSrc extends HTMLPurifier_AttrTransform {
	/**
	 * @param array $attr
	 * @param HTMLPurifier_Config $config
	 * @param HTMLPurifier_Context $context
	 * @return array
	 */
	public function transform($attr, $config, $context) {
		if ( $context->get('CurrentToken')->name !== 'img' ||
			!isset($attr['src'])) {
			return $attr;
		}

		$attr['data-original-src'] = $attr['src'];
		$attr['src'] = Util::imagePath('mail', 'blocked-image.png');
		return $attr;
	}
}
