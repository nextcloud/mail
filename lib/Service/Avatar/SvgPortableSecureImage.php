<?php

declare(strict_types=1);

/**
 * @copyright 2020 Gregor Mitzka <gregor.mitzka@gmail.com>
 *
 * @author Gregor Mitzka <gregor.mitzka@gmail.com>
 *
 * Based on the work of Mouadd Boukiaou (2020).
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

namespace OCA\Mail\Service\Avatar;

use Closure;
use Imagick;
use ImagickPixel;
use SimpleXMLElement;
use Throwable;
//use OCA\Mail\Exception\ServiceException;

/**
 * @see <a href="https://github.com/authindicators/svg-ps-converters/blob/master/illustrator-script/SaveAsSVGTinyPS_v0.1.jsx" />
 * @see <a href="https://www.w3.org/TR/SVGTiny12/intro.html" />
 * @see <a href="https://bimigroup.org/creating-bimi-svg-logo-files/" />
 */
class SvgPortableSecureImage {
	/**
	 * List of allowed element names.
	 *
	 * @var string[]
	 */
	private static $ALLOWED_ELEMENT_NAMES = [
		'circle',
		'defs',
		'desc',
		'ellipse',
		'g',
		'line',
		'linearGradient',
		'path',
		'polygon',
		'polyline',
		'radialGradient',
		'rect',
		'solidColor',
		'svg',
		'text',
		'textArea',
		'title',
		'use',
	];

	/**
	 * List of allowed attributes for each allowed element type.
	 *
	 * @var array<string,string[]>
	 */
	private static $ALLOWED_ATTRIBUTES = [
		// <circle />
		'circle' => [
			'about',
			'class',
			'color',
			'color-rendering',
			'content',
			'cx',
			'cy',
			'datatype',
			'direction',
			'display-align',
			'fill',
			'fill-opacity',
			'fill-rule',
			'font-family',
			'font-size',
			'font-style',
			'font-variant',
			'font-weight',
			'id',
			'line-increment',
			'property',
			'r',
			'rel',
			'requiredFonts',
			'resource',
			'rev',
			'role',
			'solid-color',
			'solid-opacity',
			'stop-color',
			'stop-opacity',
			'stroke',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-opacity',
			'stroke-width',
			'systemLanguage',
			'text-align',
			'text-anchor',
			'transform',
			'typeof',
			'unicode-bidi',
			'vector-effect',
			'xml:base',
			'xml:id',
			'xml:lang',
			'xml:space',
		],

		// <defs />
		'defs' => [
			'about',
			'class',
			'color',
			'color-rendering',
			'content',
			'datatype',
			'direction',
			'display-align',
			'fill',
			'fill-opacity',
			'fill-rule',
			'font-family',
			'font-size',
			'font-style',
			'font-variant',
			'font-weight',
			'id',
			'line-increment',
			'property',
			'rel',
			'resource',
			'rev',
			'role',
			'solid-color',
			'solid-opacity',
			'stop-color',
			'stop-opacity',
			'stroke',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-opacity',
			'stroke-width',
			'text-align',
			'text-anchor',
			'typeof',
			'unicode-bidi',
			'vector-effect',
			'xml:base',
			'xml:id',
			'xml:lang',
			'xml:space',
		],

		// <desc />
		'desc' => [
			'about',
			'buffered-rendering',
			'class',
			'content',
			'datatype',
			'display',
			'id',
			'image-rendering',
			'property',
			'rel',
			'requiredFonts',
			'resource',
			'rev',
			'role',
			'shape-rendering',
			'systemLanguage',
			'text-rendering',
			'typeof',
			'viewport-fill',
			'viewport-fill-opacity',
			'visibility',
			'xml:base',
			'xml:id',
			'xml:lang',
			'xml:space',
		],

		// <ellipse />
		'ellipse' => [
			'about',
			'class',
			'color',
			'color-rendering',
			'content',
			'cx',
			'cy',
			'datatype',
			'direction',
			'display-align',
			'fill',
			'fill-opacity',
			'fill-rule',
			'font-family',
			'font-size',
			'font-style',
			'font-variant',
			'font-weight',
			'id',
			'line-increment',
			'property',
			'rel',
			'requiredFonts',
			'resource',
			'rev',
			'role',
			'rx',
			'ry',
			'solid-color',
			'solid-opacity',
			'stop-color',
			'stop-opacity',
			'stroke',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-opacity',
			'stroke-width',
			'systemLanguage',
			'text-align',
			'text-anchor',
			'transform',
			'typeof',
			'unicode-bidi',
			'vector-effect',
			'xml:base',
			'xml:id',
			'xml:lang',
			'xml:space',
		],

		// <g />
		'g' => [
			'about',
			'class',
			'color',
			'color-rendering',
			'content',
			'datatype',
			'direction',
			'display-align',
			'fill',
			'fill-opacity',
			'fill-rule',
			'font-family',
			'font-size',
			'font-style',
			'font-variant',
			'font-weight',
			'id',
			'line-increment',
			'property',
			'rel',
			'requiredFonts',
			'resource',
			'rev',
			'role',
			'solid-color',
			'solid-opacity',
			'stop-color',
			'stop-opacity',
			'stroke',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-opacity',
			'stroke-width',
			'systemLanguage',
			'text-align',
			'text-anchor',
			'transform',
			'typeof',
			'unicode-bidi',
			'vector-effect',
			'xml:base',
			'xml:id',
			'xml:lang',
			'xml:space',
		],

		// <line />
		'line' => [
			'about',
			'class',
			'color',
			'color-rendering',
			'content',
			'datatype',
			'direction',
			'display-align',
			'fill',
			'fill-opacity',
			'fill-rule',
			'font-family',
			'font-size',
			'font-style',
			'font-variant',
			'font-weight',
			'id',
			'line-increment',
			'property',
			'rel',
			'requiredFonts',
			'resource',
			'rev',
			'role',
			'solid-color',
			'solid-opacity',
			'stop-color',
			'stop-opacity',
			'stroke',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-opacity',
			'stroke-width',
			'systemLanguage',
			'text-align',
			'text-anchor',
			'transform',
			'typeof',
			'unicode-bidi',
			'vector-effect',
			'x1',
			'x2',
			'xml:base',
			'xml:id',
			'xml:lang',
			'xml:space',
			'y1',
			'y2',
		],

		// <linearGradient />
		'linearGradient' => [
			'about',
			'class',
			'color',
			'color-rendering',
			'content',
			'datatype',
			'direction',
			'display-align',
			'fill',
			'fill-opacity',
			'fill-rule',
			'font-family',
			'font-size',
			'font-style',
			'font-variant',
			'font-weight',
			'gradientUnits',
			'id',
			'line-increment',
			'property',
			'rel',
			'resource',
			'rev',
			'role',
			'solid-color',
			'solid-opacity',
			'stop-color',
			'stop-opacity',
			'stroke',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-opacity',
			'stroke-width',
			'text-align',
			'text-anchor',
			'typeof',
			'unicode-bidi',
			'vector-effect',
			'x1',
			'x2',
			'xml:base',
			'xml:id',
			'xml:lang',
			'xml:space',
			'y1',
			'y2',
		],

		// <path />
		'path' => [
			'about',
			'class',
			'color',
			'color-rendering',
			'content',
			'd',
			'datatype',
			'direction',
			'display-align',
			'fill',
			'fill-opacity',
			'fill-rule',
			'font-family',
			'font-size',
			'font-style',
			'font-variant',
			'font-weight',
			'id',
			'line-increment',
			'pathLength',
			'property',
			'rel',
			'requiredFonts',
			'resource',
			'rev',
			'role',
			'solid-color',
			'solid-opacity',
			'stop-color',
			'stop-opacity',
			'stroke',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-opacity',
			'stroke-width',
			'systemLanguage',
			'text-align',
			'text-anchor',
			'transform',
			'typeof',
			'unicode-bidi',
			'vector-effect',
			'xml:base',
			'xml:id',
			'xml:lang',
			'xml:space',
		],

		// <polygon />
		'polygon' => [
			'about',
			'class',
			'color',
			'color-rendering',
			'content',
			'datatype',
			'direction',
			'display-align',
			'fill',
			'fill-opacity',
			'fill-rule',
			'font-family',
			'font-size',
			'font-style',
			'font-variant',
			'font-weight',
			'id',
			'line-increment',
			'points',
			'property',
			'rel',
			'requiredFonts',
			'resource',
			'rev',
			'role',
			'solid-color',
			'solid-opacity',
			'stop-color',
			'stop-opacity',
			'stroke',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-opacity',
			'stroke-width',
			'systemLanguage',
			'text-align',
			'text-anchor',
			'transform',
			'typeof',
			'unicode-bidi',
			'vector-effect',
			'xml:base',
			'xml:id',
			'xml:lang',
			'xml:space',
		],

		// <polyline />
		'polyline' => [
			'about',
			'class',
			'color',
			'color-rendering',
			'content',
			'datatype',
			'direction',
			'display-align',
			'fill',
			'fill-opacity',
			'fill-rule',
			'font-family',
			'font-size',
			'font-style',
			'font-variant',
			'font-weight',
			'id',
			'line-increment',
			'points',
			'property',
			'rel',
			'requiredFonts',
			'resource',
			'rev',
			'role',
			'solid-color',
			'solid-opacity',
			'stop-color',
			'stop-opacity',
			'stroke',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-opacity',
			'stroke-width',
			'systemLanguage',
			'text-align',
			'text-anchor',
			'transform',
			'typeof',
			'unicode-bidi',
			'vector-effect',
			'xml:base',
			'xml:id',
			'xml:lang',
			'xml:space',
		],

		// <radialGradient />
		'radialGradient' => [
			'about',
			'class',
			'color',
			'color-rendering',
			'content',
			'cx',
			'cy',
			'datatype',
			'direction',
			'display-align',
			'fill',
			'fill-opacity',
			'fill-rule',
			'font-family',
			'font-size',
			'font-style',
			'font-variant',
			'font-weight',
			'gradientUnits',
			'id',
			'line-increment',
			'property',
			'r',
			'rel',
			'resource',
			'rev',
			'role',
			'solid-color',
			'solid-opacity',
			'stop-color',
			'stop-opacity',
			'stroke',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-opacity',
			'stroke-width',
			'text-align',
			'text-anchor',
			'typeof',
			'unicode-bidi',
			'vector-effect',
			'xml:base',
			'xml:id',
			'xml:lang',
			'xml:space',
		],

		// <rect />
		'rect' => [
			'about',
			'class',
			'color',
			'color-rendering',
			'content',
			'datatype',
			'direction',
			'display-align',
			'fill',
			'fill-opacity',
			'fill-rule',
			'font-family',
			'font-size',
			'font-style',
			'font-variant',
			'font-weight',
			'height',
			'id',
			'line-increment',
			'property',
			'rel',
			'requiredFonts',
			'resource',
			'rev',
			'role',
			'rx',
			'ry',
			'solid-color',
			'solid-opacity',
			'stop-color',
			'stop-opacity',
			'stroke',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-opacity',
			'stroke-width',
			'systemLanguage',
			'text-align',
			'text-anchor',
			'transform',
			'typeof',
			'unicode-bidi',
			'vector-effect',
			'width',
			'x',
			'xml:base',
			'xml:id',
			'xml:lang',
			'xml:space',
			'y',
		],

		// <solidColor />
		'solidColor' => [
			'about',
			'class',
			'color',
			'color-rendering',
			'content',
			'datatype',
			'direction',
			'display-align',
			'fill',
			'fill-opacity',
			'fill-rule',
			'font-family',
			'font-size',
			'font-style',
			'font-variant',
			'font-weight',
			'id',
			'line-increment',
			'property',
			'rel',
			'resource',
			'rev',
			'role',
			'solid-color',
			'solid-opacity',
			'stop-color',
			'stop-opacity',
			'stroke',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-opacity',
			'stroke-width',
			'text-align',
			'text-anchor',
			'typeof',
			'unicode-bidi',
			'vector-effect',
			'xml:base',
			'xml:id',
			'xml:lang',
			'xml:space',
		],

		// <svg />
		'svg' => [
			'about',
			'baseProfile',
			'class',
			'color',
			'color-rendering',
			'content',
			'contentScriptType',
			'datatype',
			'direction',
			'display-align',
			'externalResourcesRequired',
			'fill',
			'fill-opacity',
			'fill-rule',
			'focusable',
			'font-family',
			'font-size',
			'font-style',
			'font-variant',
			'font-weight',
			'height',
			'line-increment',
			'playbackOrder',
			'preserveAspectRatio',
			'property',
			'rel',
			'resource',
			'rev',
			'role',
			'snapshotTime',
			'solid-color',
			'solid-opacity',
			'stop-color',
			'stop-opacity',
			'stroke',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-opacity',
			'stroke-width',
			'text-align',
			'text-anchor',
			'timelineBegin',
			'typeof',
			'unicode-bidi',
			'vector-effect',
			'version',
			'viewBox',
			'width',
			'xml:base',
			'xml:lang',
			'xml:space',
			'zoomAndPan'
		],

		// <text />
		'text' => [
			'about',
			'class',
			'color',
			'color-rendering',
			'content',
			'datatype',
			'direction',
			'display-align',
			'editable',
			'fill',
			'fill-opacity',
			'fill-rule',
			'font-family',
			'font-size',
			'font-style',
			'font-variant',
			'font-weight',
			'id',
			'line-increment',
			'property',
			'rel',
			'requiredFonts',
			'resource',
			'rev',
			'role',
			'rotate',
			'solid-color',
			'solid-opacity',
			'stop-color',
			'stop-opacity',
			'stroke',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-opacity',
			'stroke-width',
			'systemLanguage',
			'text-align',
			'text-anchor',
			'transform',
			'typeof',
			'unicode-bidi',
			'vector-effect',
			'x',
			'xml:base',
			'xml:id',
			'xml:lang',
			'xml:space',
			'y',
		],

		// <textArea />
		'textArea' => [
			'about',
			'class',
			'color',
			'color-rendering',
			'content',
			'datatype',
			'direction',
			'display-align',
			'fill',
			'fill-opacity',
			'fill-rule',
			'font-family',
			'font-size',
			'font-style',
			'font-variant',
			'font-weight',
			'height',
			'id',
			'line-increment',
			'property',
			'rel',
			'requiredFonts',
			'resource',
			'rev',
			'role',
			'solid-color',
			'solid-opacity',
			'stop-color',
			'stop-opacity',
			'stroke',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-opacity',
			'stroke-width',
			'systemLanguage',
			'text-align',
			'text-anchor',
			'transform',
			'typeof',
			'unicode-bidi',
			'vector-effect',
			'width',
			'x',
			'xml:base',
			'xml:id',
			'xml:lang',
			'xml:space',
			'y',
		],

		// <title />
		'title' => [
			'about',
			'buffered-rendering',
			'class',
			'content',
			'datatype',
			'display',
			'id',
			'image-rendering',
			'property',
			'rel',
			'requiredFonts',
			'resource',
			'rev',
			'role',
			'shape-rendering',
			'systemLanguage',
			'text-rendering',
			'typeof',
			'viewport-fill',
			'viewport-fill-opacity',
			'visibility',
			'xml:base',
			'xml:id',
			'xml:lang',
			'xml:space',
		],

		// <use />
		'use' => [
			'about',
			'class',
			'color',
			'color-rendering',
			'content',
			'datatype',
			'direction',
			'display-align',
			'fill',
			'fill-opacity',
			'fill-rule',
			'font-family',
			'font-size',
			'font-style',
			'font-variant',
			'font-weight',
			'href',
			'id',
			'line-increment',
			'property',
			'rel',
			'requiredFonts',
			'resource',
			'rev',
			'role',
			'solid-color',
			'solid-opacity',
			'stop-color',
			'stop-opacity',
			'stroke',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-opacity',
			'stroke-width',
			'systemLanguage',
			'text-align',
			'text-anchor',
			'transform',
			'typeof',
			'unicode-bidi',
			'vector-effect',
			'x',
			'xml:base',
			'xml:id',
			'xml:lang',
			'xml:space',
			'y',
		],
	];

	/**
	 * @var string
	 */
	private $data;

	/**
	 * @param string $data
	 */
	public function __construct(string $data) {
		$this->data = $data;
	}

	/**
	 * Returns true if SVG image conforms
	 * to SVG Portable/Secure format, otherwise false.
	 *
	 * @return bool
	 */
	public function isValid(): bool {
		return $this->suppressLibXmlErrors(function () {
			$doc = new SimpleXMLElement($this->data);
			$elements = $doc->xpath('descendant-or-self::*');

			foreach ($elements as $element) {
				$element_name = $element->getName();

				if (!$this->isAllowedElementName($element_name)) {
					return false;
				}

				foreach ($element->getNamespaces() as $ns) {
					foreach ($element->attributes($ns) as $attr) {
						$attr_allowed = $this->isAllowedAttributeName(
							$attr->getName(),
							$element_name
						);

						if (!$attr_allowed) {
							return false;
						}
					}
				}
			}

			return true;
		});
	}

	/**
	 * Convert the SVG image to PNG format
	 * of size ($size by $size) pixels and returns
	 * it as an instance of Imagick.
	 *
	 * @param int $size (default: 128):
	 *		x and y dimensions of the resulting PNG image.
	 *
	 * @return \Imagick
	 *
	 * @throws \OCA\Mail\Exception\ServiceException
	 */
	public function toPngImage(int $size = 128): Imagick {
		if (!$this->isValid()) {
			throw new ServiceException(
				'cannot convert SVG P/S image to PNG: image has an invalid format'
			);
		}

		if (!class_exists('\\Imagick')) {
			throw new ServiceException(
				'cannot convert SVG P/S image to PNG: class Imagick does not exist'
			);
		}

		$image = new Imagick();

		$image->readImageBlob($this->data);
		$image->setImageBackgroundColor(
			new ImagickPixel('transparent')
		);

		$image->setImageFormat('png24');
		$image->resizeImage(
			$size, // x
			$size, // y
			Imagick::FILTER_LANCZOS,
			1 // no blur
		);

		return $image;
	}

	/**
	 * @param int $size (default: 128)
	 *
	 * @return string
	 *
	 * @throws \OCA\Mail\Exception\ServiceException {@see #toPngImage}
	 */
	public function toPngImageDataUrl(int $size = 128): string {
		$image = $this->toPngImage($size);
		$blob = $image->getImageBlob();
		$image->clear();

		return sprintf(
			'data:image/png;base64,%s',
			base64_encode($blob)
		);
	}

	/**
	 * Returns true if element name is allowed, otherwise false.
	 *
	 * @param string $element_name
	 *
	 * @return bool
	 */
	private function isAllowedElementName(
		string $element_name
	): bool {
		return in_array(
			$element_name,
			self::$ALLOWED_ELEMENT_NAMES
		);
	}

	/**
	 * Returns true if attribute name is allowed
	 * for the passed element type, otherwise false.
	 *
	 * @param string $attr_name
	 * @param string $element_name
	 *
	 * @return bool
	 */
	private function isAllowedAttributeName(
		string $attr_name,
		string $element_name
	): bool {
		return in_array(
			$attr_name,
			self::$ALLOWED_ATTRIBUTES[$element_name]
		);
	}

	/**
	 * Suppresses error messages from LibXML
	 * and returns the return value from the callback.
	 *
	 * @param \Closure $callback
	 *
	 * @return bool
	 */
	private function suppressLibXmlErrors(Closure $callback) {
		$prev_use_errors = libxml_use_internal_errors(true);

		try {
			return $callback();
		} catch (Throwable $ex) {
			if (!$this->isExceptionFromLibXml($ex)) {
				throw $ex;
			}

			return false;
		} finally {
			libxml_use_internal_errors(
				$prev_use_errors
			);
		}
	}

	/**
	 * Check if a throwable occurred in SimpleXMLElement.
	 *
	 * @param \Throwable $ex
	 *
	 * @return bool
	 */
	private function isExceptionFromLibXml(
		Throwable $ex
	): bool {
		$trace = $ex->getTrace();

		return (
			$trace[0]['class'] === SimpleXMLElement::class
		);
	}
}
